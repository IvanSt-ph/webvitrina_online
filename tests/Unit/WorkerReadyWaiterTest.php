<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Tests\Support\WorkerReadyWaiter;

// No application bootstrap, real child processes or database connections.
class WorkerReadyWaiterTest extends TestCase
{
    public static function outputSequences(): array
    {
        return [
            'already buffered' => [["READY\n"], [true], true],
            'partial stdout' => [['RE', 'READY', "READY\n"], [true, true, true], true],
            'partial CRLF' => [['RE', "READY\r", "READY\r\n"], [true, true, true], true],
            'exit with READY' => [["READY\n"], [false], false],
            'exit without READY' => [["startup\n"], [false], false],
            'incomplete line then exit' => [['READY', 'READY'], [true, false], false],
            'not a standalone READY line' => [["NOT_READY\nREADY_EXTRA\n", "NOT_READY\nREADY_EXTRA\n"], [true, false], false],
        ];
    }

    #[DataProvider('outputSequences')]
    public function test_ready_requires_a_complete_buffered_line_and_live_worker(array $outputs, array $running, bool $expected): void
    {
        $worker = $this->worker();
        $worker->expects($this->exactly(count($outputs)))->method('getOutput')->willReturnOnConsecutiveCalls(...$outputs);
        $worker->expects($this->exactly(count($running)))->method('isRunning')->willReturnOnConsecutiveCalls(...$running);
        $worker->expects($this->exactly(2 * count($outputs)))->method('checkTimeout');

        $this->assertSame($expected, WorkerReadyWaiter::wait($worker));
        $this->assertSame(30.0, $worker->getTimeout());
    }

    public function test_output_consumed_by_status_polling_is_detected_on_the_next_iteration(): void
    {
        $worker = $this->worker();
        $buffer = '';
        $worker->expects($this->exactly(2))->method('getOutput')->willReturnCallback(static function () use (&$buffer): string {
            return $buffer;
        });
        $worker->expects($this->exactly(2))->method('isRunning')->willReturnCallback(static function () use (&$buffer): bool {
            // Emulate Windows updateStatus reading READY outside waitUntil's callback.
            $buffer = "READY\n";

            return true;
        });
        $worker->expects($this->exactly(4))->method('checkTimeout');

        $this->assertTrue(WorkerReadyWaiter::wait($worker));
    }

    public static function timeoutOutputs(): array
    {
        return ['no READY' => [''], 'READY at deadline' => ["READY\n"]];
    }

    #[DataProvider('timeoutOutputs')]
    public function test_original_process_timeout_is_propagated_even_if_ready_arrives(string $output): void
    {
        $worker = $this->worker();
        $worker->expects($this->once())->method('getOutput')->willReturn($output);
        $worker->expects($this->once())->method('isRunning')->willReturn(true);
        $timeout = new ProcessTimedOutException($worker, ProcessTimedOutException::TYPE_GENERAL);
        $checks = 0;
        $worker->expects($this->exactly(2))->method('checkTimeout')->willReturnCallback(static function () use (&$checks, $timeout): void {
            if (++$checks === 2) {
                throw $timeout;
            }
        });

        try {
            WorkerReadyWaiter::wait($worker);
            $this->fail('An expired process timeout must not be accepted as readiness.');
        } catch (ProcessTimedOutException $exception) {
            $this->assertSame($timeout, $exception);
            $this->assertSame(30.0, $exception->getExceededTimeout());
        }
    }

    public function test_unbounded_process_is_rejected(): void
    {
        $worker = new Process(['unused-worker'], timeout: null);
        $this->expectException(\InvalidArgumentException::class);
        WorkerReadyWaiter::wait($worker);
    }

    private function worker(): Process
    {
        $worker = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([['unused-worker'], null, null, null, 30])
            ->onlyMethods(['getOutput', 'isRunning', 'checkTimeout', 'start', 'waitUntil'])
            ->getMock();
        $worker->expects($this->never())->method('start');
        $worker->expects($this->never())->method('waitUntil');

        return $worker;
    }
}
