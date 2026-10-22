<?php

namespace Tests\Unit;

use App\Database\SafeDatabaseTransactionRecord;
use App\Database\SafeDatabaseTransactionsManager;
use Illuminate\Database\DatabaseTransactionRecord;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/** Pure manager contracts: no Laravel application, PDO, migrations or test transaction manager. */
class SafeDatabaseTransactionsManagerTest extends TestCase
{
    public function test_commit_runs_all_callbacks_and_rethrows_the_first_object_even_if_logger_fails(): void
    {
        $trace = [];
        $first = new RuntimeException('first');
        $second = new \Error('second');
        $manager = new SafeDatabaseTransactionsManager(function ($error) use (&$trace): void {
            $trace[] = $error->getMessage();
            throw new RuntimeException('logger');
        });
        $manager->begin('mysql', 1);
        $manager->addCallback(fn () => throw $first);
        $manager->addCallback(fn () => throw $second);
        $manager->addCallback(function () use (&$trace): void { $trace[] = 'release'; });
        $manager->addCallbackForRollback(fn () => $this->fail('Commit must not compensate files.'));
        $this->assertThrowsSame($first, fn () => $manager->commit('mysql', 1, 0));
        $this->assertSame(['release', 'first', 'second'], $trace);
        $this->assertEmptyManager($manager);
        $manager->begin('mysql', 1);
        $this->assertNull($manager->getPendingTransactions()->last()->parent);
        $manager->commit('mysql', 1, 0);
        $this->assertSame(['release', 'first', 'second'], $trace);
    }

    public function test_rollback_keeps_original_business_exception_and_never_replays_old_commit(): void
    {
        $trace = [];
        $manager = new SafeDatabaseTransactionsManager(function () use (&$trace): void {
            $trace[] = 'report';
            throw new RuntimeException('logger');
        });
        $original = new RuntimeException('business');
        $manager->begin('mysql', 1);
        $manager->addCallback(fn () => $this->fail('Rolled back callback replayed.'));
        $manager->addCallbackForRollback(fn () => throw new \Error('cleanup'));
        $manager->addCallbackForRollback(function () use (&$trace): void { $trace[] = 'release'; });
        $this->assertThrowsSame($original, function () use ($manager, $original): void {
            try {
                throw $original;
            } catch (Throwable $exception) {
                $manager->rollback('mysql', 0);
                throw $exception;
            }
        });
        $this->assertSame(['release', 'report'], $trace);
        $this->assertEmptyManager($manager);
        $manager->begin('mysql', 1);
        $this->assertNull($manager->getPendingTransactions()->last()->parent);
        $manager->addCallback(function () use (&$trace): void { $trace[] = 'new'; });
        $manager->commit('mysql', 1, 0);
        $this->assertSame(['release', 'report', 'new'], $trace);
    }

    public function test_nested_commit_waits_for_root_and_failure_does_not_skip_parent(): void
    {
        $trace = [];
        $error = new RuntimeException('child');
        $manager = new SafeDatabaseTransactionsManager(static fn () => null);
        $manager->begin('mysql', 1);
        $manager->addCallback(function () use (&$trace): void { $trace[] = 'root'; });
        $manager->begin('mysql', 2);
        $manager->addCallback(function () use (&$trace, $error): void { $trace[] = 'child'; throw $error; });
        $manager->commit('mysql', 2, 1);
        $this->assertSame([], $trace);
        $this->assertThrowsSame($error, fn () => $manager->commit('mysql', 1, 0));
        $this->assertSame(['child', 'root'], $trace);
        $this->assertEmptyManager($manager);
    }

    public function test_savepoint_rollback_preserves_parent_and_discards_committed_descendants(): void
    {
        $trace = [];
        $manager = new SafeDatabaseTransactionsManager(static fn () => null);
        $manager->begin('mysql', 1);
        $root = $manager->getPendingTransactions()->last();
        $manager->addCallback(function () use (&$trace): void { $trace[] = 'root commit'; });
        $manager->addCallbackForRollback(fn () => $this->fail('Parent was not rolled back.'));
        $manager->begin('mysql', 2);
        $manager->addCallback(fn () => $this->fail('Savepoint was rolled back.'));
        $manager->addCallbackForRollback(fn () => throw new RuntimeException('child rollback'));
        $manager->addCallbackForRollback(function () use (&$trace): void { $trace[] = 'child rollback'; });
        $manager->begin('mysql', 3);
        $manager->addCallback(fn () => $this->fail('Committed descendant must be discarded.'));
        $manager->commit('mysql', 3, 2);
        $manager->rollback('mysql', 1);
        $this->assertSame([$root], $manager->getPendingTransactions()->all());
        $this->assertCount(0, $manager->getCommittedTransactions());
        $this->assertSame(['child rollback'], $trace);
        $manager->commit('mysql', 1, 0);
        $this->assertSame(['child rollback', 'root commit'], $trace);
    }

    public function test_root_rollback_runs_active_child_then_parent_despite_failure(): void
    {
        $trace = [];
        $manager = new SafeDatabaseTransactionsManager(static fn () => null);
        $manager->begin('mysql', 1);
        $manager->addCallbackForRollback(function () use (&$trace): void { $trace[] = 'root'; });
        $manager->begin('mysql', 2);
        $manager->addCallbackForRollback(fn () => throw new RuntimeException('child'));
        $manager->addCallbackForRollback(function () use (&$trace): void { $trace[] = 'child'; });
        $manager->rollback('mysql', 0);
        $this->assertSame(['child', 'root'], $trace);
        $this->assertEmptyManager($manager);
    }

    public function test_reentrant_commit_has_its_own_error_and_clean_parent(): void
    {
        $trace = [];
        $outer = new RuntimeException('outer');
        $inner = new RuntimeException('inner');
        $observedInner = null;
        $stateBeforeInner = null;
        $innerHasNoParent = null;
        $manager = new SafeDatabaseTransactionsManager(static fn () => null);
        $manager->begin('mysql', 1);
        $manager->addCallback(fn () => throw $outer);
        $manager->addCallback(function () use (
            $manager, $inner, &$trace, &$observedInner, &$stateBeforeInner, &$innerHasNoParent,
        ): void {
            $stateBeforeInner = [
                $manager->getPendingTransactions()->count(),
                $manager->getCommittedTransactions()->count(),
            ];
            $manager->begin('mysql', 1);
            $innerHasNoParent = $manager->getPendingTransactions()->last()->parent === null;
            $manager->addCallback(fn () => throw $inner);
            $manager->addCallback(function () use (&$trace): void { $trace[] = 'inner release'; });
            try {
                $manager->commit('mysql', 1, 0);
            } catch (Throwable $exception) {
                $observedInner = $exception;
            }
        });
        $manager->addCallback(function () use (&$trace): void { $trace[] = 'outer release'; });
        $this->assertThrowsSame($outer, fn () => $manager->commit('mysql', 1, 0));
        $this->assertSame($inner, $observedInner);
        $this->assertSame([0, 0], $stateBeforeInner);
        $this->assertTrue($innerHasNoParent);
        $this->assertSame(['inner release', 'outer release'], $trace);
        $this->assertEmptyManager($manager);
    }

    public function test_rollback_callback_can_commit_new_transaction_without_stale_records(): void
    {
        $trace = [];
        $manager = new SafeDatabaseTransactionsManager(static fn () => null);
        $manager->begin('mysql', 1);
        $manager->addCallbackForRollback(function () use ($manager, &$trace): void {
            $this->assertEmptyManager($manager);
            $manager->begin('mysql', 1);
            $this->assertNull($manager->getPendingTransactions()->last()->parent);
            $manager->addCallback(function () use (&$trace): void { $trace[] = 'new commit'; });
            $manager->commit('mysql', 1, 0);
        });
        $manager->addCallbackForRollback(function () use (&$trace): void { $trace[] = 'old rollback'; });
        $manager->rollback('mysql', 0);
        $this->assertSame(['new commit', 'old rollback'], $trace);
        $this->assertEmptyManager($manager);
    }

    public function test_connections_finalize_independently(): void
    {
        $trace = [];
        $error = new RuntimeException('a');
        $manager = new SafeDatabaseTransactionsManager(static fn () => null);
        $manager->begin('a', 1);
        $a = $manager->getPendingTransactions()->last();
        $a->addCallback(fn () => throw $error);
        $manager->begin('b', 1);
        $b = $manager->getPendingTransactions()->last();
        $b->addCallbackForRollback(function () use (&$trace): void { $trace[] = 'b'; });
        $this->assertThrowsSame($error, fn () => $manager->commit('a', 1, 0));
        $this->assertSame([$b], $manager->getPendingTransactions()->all());
        $this->assertSame([], $trace);
        $manager->rollback('b', 0);
        $this->assertSame(['b'], $trace);
        $this->assertEmptyManager($manager);
    }

    public function test_callbacks_outside_transaction_keep_laravel_semantics(): void
    {
        $error = new RuntimeException('immediate');
        $manager = new SafeDatabaseTransactionsManager(static fn () => null);
        $this->assertThrowsSame($error, fn () => $manager->addCallback(fn () => throw $error));
        $manager->addCallbackForRollback(fn () => $this->fail('No active transaction.'));
        $this->assertEmptyManager($manager);
    }

    public function test_overridden_method_signatures_match_installed_laravel(): void
    {
        $this->assertSame('12.69.2', Application::VERSION);
        foreach ([
            [SafeDatabaseTransactionsManager::class, DatabaseTransactionsManager::class, ['begin', 'commit', 'rollback']],
            [SafeDatabaseTransactionRecord::class, DatabaseTransactionRecord::class, ['executeCallbacks', 'executeCallbacksForRollback']],
        ] as [$child, $parent, $methods]) {
            foreach ($methods as $method) {
                $actual = new ReflectionMethod($child, $method);
                $expected = new ReflectionMethod($parent, $method);
                $this->assertTrue($actual->isPublic());
                $this->assertSame((string) $expected->getReturnType(), (string) $actual->getReturnType());
                $this->assertSame($expected->getNumberOfParameters(), $actual->getNumberOfParameters());
                foreach ($expected->getParameters() as $index => $parameter) {
                    $override = $actual->getParameters()[$index];
                    $this->assertSame($parameter->getName(), $override->getName());
                    $this->assertSame((string) $parameter->getType(), (string) $override->getType());
                    $this->assertSame($parameter->isOptional(), $override->isOptional());
                }
            }
        }
    }

    private function assertEmptyManager(SafeDatabaseTransactionsManager $manager): void
    {
        $this->assertCount(0, $manager->getPendingTransactions());
        $this->assertCount(0, $manager->getCommittedTransactions());
    }

    private function assertThrowsSame(Throwable $expected, callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $actual) {
            $this->assertSame($expected, $actual);
            return;
        }
        $this->fail('Expected the original exception object.');
    }
}
