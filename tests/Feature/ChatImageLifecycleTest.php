<?php

namespace Tests\Feature;

use App\Http\Controllers\ChatController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatImageService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ChatImageLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Exercise real final commits, not testing-manager simulated afterCommit.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function controllers(): array
    {
        return ['participant' => [false], 'admin support' => [true]];
    }

    #[DataProvider('controllers')]
    public function test_image_is_prepared_outside_transaction_and_sent_privately(bool $admin): void
    {
        $fixture = $this->fixture($admin);
        $images = new ChatImageService();
        $this->partialMock(ChatImageService::class)->shouldReceive('prepare')->once()
            ->andReturnUsing(function ($file) use ($images): string {
                $this->assertSame(0, DB::transactionLevel());

                return $images->prepare($file);
            });
        $fixture['conversation']->update([$admin ? 'buyer_deleted_at' : 'seller_deleted_at' => now()]);
        $this->send($fixture);
        $message = Message::sole();
        Storage::disk('local')->assertExists($message->image_path);
        Storage::disk('public')->assertMissing($message->image_path);
        $this->assertNull($fixture['conversation']->fresh()->{$admin ? 'buyer_deleted_at' : 'seller_deleted_at'});
        $this->assertSame($fixture['sender']->id, $message->sender_id);
        $this->assertSame('image body', $message->body);
        $this->assertSame(0, DB::transactionLevel());

        $route = $admin ? 'admin.chats.messages.image' : 'chats.messages.image';
        $this->actingAs($fixture['sender'])->get(route($route, [$fixture['conversation'], $message]))->assertOk();
        $outsider = User::factory()->create();
        $this->actingAs($outsider)->get(route('chats.messages.image', [$fixture['conversation'], $message]))->assertNotFound();
    }

    #[DataProvider('controllers')]
    public function test_failed_insert_cleans_the_uploaded_file(bool $admin): void
    {
        $fixture = $this->fixture($admin);
        $this->withMessageEvent('creating', function (Message $message): void {
            Storage::disk('local')->assertExists($message->image_path);
            $message->conversation_id = 0; // Real MySQL foreign-key INSERT failure.
        }, function () use ($fixture): void {
            try {
                $this->send($fixture);
                $this->fail('Expected a foreign-key failure.');
            } catch (QueryException $exception) {
                $this->assertSame(1452, (int) $exception->errorInfo[1]);
            }
        });
        $this->assertSame(0, Message::count());
        $this->assertSame([], Storage::disk('local')->allFiles('chat-images'));
    }

    #[DataProvider('controllers')]
    public function test_error_after_insert_rolls_back_message_and_file(bool $admin): void
    {
        $fixture = $this->fixture($admin);
        $this->withMessageEvent('created', fn () => throw new \RuntimeException('after insert'), function () use ($fixture): void {
            try {
                $this->send($fixture);
                $this->fail('Expected failure after insertion.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('after insert', $exception->getMessage());
            }
        });
        $this->assertSame(0, Message::count());
        $this->assertSame([], Storage::disk('local')->allFiles('chat-images'));
    }

    #[DataProvider('controllers')]
    public function test_ancestor_rollback_cleans_file_after_child_savepoint_committed(bool $admin): void
    {
        $fixture = $this->fixture($admin);
        DB::beginTransaction();
        try {
            DB::transaction(fn () => $this->send($fixture));
            $path = Message::sole()->image_path;
            Storage::disk('local')->assertExists($path);
        } finally {
            DB::rollBack();
        }
        $this->assertSame(0, Message::count());
        $this->assertSame([], Storage::disk('local')->allFiles('chat-images'));
        $this->assertSame(0, DB::table('user_notifications')->count());
    }

    #[DataProvider('controllers')]
    public function test_savepoint_rollback_then_outer_commit_does_not_keep_file(bool $admin): void
    {
        $fixture = $this->fixture($admin);
        DB::transaction(function () use ($fixture): void {
            DB::beginTransaction();
            try {
                $this->send($fixture);
            } finally {
                DB::rollBack();
            }
            $this->assertSame([], Storage::disk('local')->allFiles('chat-images'));
        });
        $this->assertSame(0, Message::count());
    }

    #[DataProvider('controllers')]
    public function test_post_commit_exception_does_not_remove_committed_file(bool $admin): void
    {
        $fixture = $this->fixture($admin);
        $this->withMessageEvent('created', function (): void {
            DB::afterCommit(fn () => throw new \RuntimeException('after commit'));
        }, function () use ($fixture): void {
            try {
                $this->send($fixture);
                $this->fail('Expected callback failure.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('after commit', $exception->getMessage());
            }
        });
        Storage::disk('local')->assertExists(Message::sole()->image_path);
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function deletions(): array
    {
        $cases = [];
        foreach ([false, true] as $admin) {
            foreach (['message', 'conversation', 'account'] as $target) {
                foreach ([false, true] as $commit) {
                    $cases[($admin ? 'admin' : 'user')." $target ".($commit ? 'commit' : 'rollback')] = [$admin, $target, $commit];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('deletions')]
    public function test_existing_files_are_deleted_only_after_final_commit(bool $admin, string $target, bool $commit): void
    {
        $fixture = $this->fixture($admin);
        $this->send($fixture);
        $message = Message::sole();
        DB::beginTransaction();
        try {
            match ($target) {
                'message' => $message->delete(),
                'conversation' => $fixture['conversation']->delete(),
                'account' => $fixture['buyer']->delete(),
            };
            Storage::disk('local')->assertExists($message->image_path);
            $this->assertNull(Message::find($message->id));
            if ($commit) {
                DB::commit();
            }
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
        if ($commit) {
            Storage::disk('local')->assertMissing($message->image_path);
            $this->assertNull(Message::find($message->id));
        } else {
            Storage::disk('local')->assertExists($message->image_path);
            $this->assertNotNull(Message::find($message->id));
        }
    }

    #[DataProvider('controllers')]
    public function test_deletion_or_moderation_after_upload_is_rechecked(bool $admin): void
    {
        foreach (['delete', 'lock'] as $action) {
            $fixture = $this->fixture($admin);
            $images = new ChatImageService();
            $this->partialMock(ChatImageService::class)->shouldReceive('storePrepared')->once()
                ->andReturnUsing(function ($bytes) use ($images, $fixture, $action): string {
                    $path = $images->storePrepared($bytes);
                    if ($action === 'delete') {
                        $fixture['conversation']->delete();
                    } else {
                        $fixture['conversation']->update(['locked_at' => now()]);
                    }

                    return $path;
                });
            try {
                $this->send($fixture);
                $this->fail('Expected stale conversation rejection.');
            } catch (ModelNotFoundException $exception) {
                $this->assertSame('delete', $action);
            } catch (HttpException $exception) {
                $this->assertSame('lock', $action);
                $this->assertSame(423, $exception->getStatusCode());
            }
            $this->assertSame([], Storage::disk('local')->allFiles('chat-images'));
        }
    }

    public function test_partial_write_is_cleaned_and_cleanup_failure_is_logged(): void
    {
        Storage::fake('local');
        $disk = Storage::disk('local');
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $path = null;
        $adapter->shouldReceive('put')->once()->andReturnUsing(function ($name, $bytes) use ($disk, &$path): bool {
            $path = $name;
            $disk->put($name, $bytes);

            return false;
        });
        $adapter->shouldReceive('delete')->once()->andReturnUsing(fn ($name) => $disk->delete($name));
        Storage::shouldReceive('disk')->with('local')->andReturn($adapter);
        try {
            app(ChatImageService::class)->storePrepared('partial');
            $this->fail('Expected failed write.');
        } catch (\RuntimeException $exception) {
            $this->assertNotNull($path);
            $this->assertFalse($disk->exists($path));
        }

        Log::spy();
        $adapter->shouldReceive('delete')->with('chat-images/failure.webp')->once()->andReturn(false);
        app(ChatImageService::class)->cleanup('chat-images/failure.webp', 'rollback');
        Log::shouldHaveReceived('error')->with('Chat image cleanup failed; retry required', \Mockery::on(
            fn ($context) => $context['path'] === 'chat-images/failure.webp' && $context['phase'] === 'rollback',
        ))->once();
    }

    public function test_unconfirmed_commit_retains_file_and_logs_reconciliation_requirement(): void
    {
        $fixture = $this->fixture(true);
        $connection = DB::connection();
        $dispatcher = $connection->getEventDispatcher();
        $isolated = clone $dispatcher;
        $connection->setEventDispatcher($isolated);
        $isolated->listen(\Illuminate\Database\Events\TransactionCommitting::class, function (): void {
            throw new \RuntimeException('commit interrupted');
        });
        Log::spy();
        try {
            $this->send($fixture);
            $this->fail('Expected commit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('commit interrupted', $exception->getMessage());
            $files = Storage::disk('local')->allFiles('chat-images');
            $this->assertCount(1, $files);
            Log::shouldHaveReceived('error')->with('Chat image transaction outcome unknown; reconciliation required', [
                'path' => $files[0],
            ])->once();
        } finally {
            $connection->setEventDispatcher($dispatcher);
            // Laravel's failed-commit handler already decremented its counter.
            // Resolve the injected failure explicitly, then notify its callbacks.
            if ($connection->getPdo()->inTransaction()) {
                $connection->getPdo()->rollBack();
            }
            app('db.transactions')->rollback($connection->getName(), 0);
        }
        $this->assertSame([], Storage::disk('local')->allFiles('chat-images'));
    }

    public function test_failed_after_commit_cleanup_does_not_undo_message_deletion(): void
    {
        $fixture = $this->fixture(true);
        $this->send($fixture);
        $message = Message::sole();
        $disk = Storage::disk('local');
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $adapter->shouldReceive('delete')->with($message->image_path)->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($adapter);
        Log::spy();
        $message->delete();
        $this->assertNull(Message::find($message->id));
        $this->assertTrue($disk->exists($message->image_path));
        Log::shouldHaveReceived('error')->with('Chat image cleanup failed; retry required', \Mockery::on(
            fn ($context) => $context['path'] === $message->image_path && $context['phase'] === 'after_commit',
        ))->once();
    }

    public function test_admin_authored_images_in_other_peoples_support_dialogue_are_removed_on_account_delete(): void
    {
        $fixture = $this->fixture(true);
        $author = User::factory()->admin()->create();
        $fixture['sender'] = $author;
        $this->send($fixture);
        $path = Message::sole()->image_path;
        $author->delete();
        $this->assertNotNull($fixture['conversation']->fresh());
        $this->assertSame(0, Message::count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_hiding_conversation_preserves_attachment(): void
    {
        $fixture = $this->fixture(false);
        $this->send($fixture);
        $message = Message::sole();
        $this->actingAs($fixture['sender'])->delete(route('chats.destroy', $fixture['conversation']))->assertRedirect();
        $this->assertNotNull(Message::find($message->id));
        Storage::disk('local')->assertExists($message->image_path);
    }

    private function fixture(bool $admin): array
    {
        Storage::fake('local');
        Storage::fake('public');
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => $admin ? 'admin' : 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'conversation_type' => $admin ? Conversation::TYPE_SUPPORT : Conversation::TYPE_MARKETPLACE,
        ]);

        return ['buyer' => $buyer, 'sender' => $admin ? $seller : $buyer, 'conversation' => $conversation, 'admin' => $admin];
    }

    private function send(array $fixture): void
    {
        $this->actingAs($fixture['sender']);
        $request = Request::create('/chat', 'POST', ['body' => 'image body'], files: ['image' => UploadedFile::fake()->image('chat.jpg')]);
        $request->setUserResolver(fn () => $fixture['sender']);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);
        app($fixture['admin'] ? AdminChatController::class : ChatController::class)->store($request, $fixture['conversation']);
    }

    private function withMessageEvent(string $event, callable $listener, callable $operation): void
    {
        $dispatcher = Message::getEventDispatcher();
        Message::setEventDispatcher(clone $dispatcher);
        Message::{$event}($listener);
        try {
            $operation();
        } finally {
            Message::setEventDispatcher($dispatcher);
        }
    }
}
