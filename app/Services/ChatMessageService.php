<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ChatMessageService
{
    public function __construct(private readonly ChatImageService $images, private readonly BackupWriteBarrier $barrier)
    {
    }

    public function send(User $sender, Conversation $conversation, string $body, ?UploadedFile $image, bool $admin = false): Message
    {
        // Decode/resize/encode before this operation acquires any DB row locks.
        $bytes = $image ? $this->images->prepare($image) : null;

        return $this->barrier->run(function () use ($sender, $conversation, $body, $bytes, $admin): Message {
            $path = $bytes !== null ? $this->images->storePrepared($bytes) : null;
            $files = new ChatImageOperation($path);
            try {
                return $this->barrier->transaction(function () use ($sender, $conversation, $body, $path, $files, $admin): Message {
                    $files->attach(DB::connection());
                    // Account deletion holds User before Conversation. Do not
                    // take a recipient User lock after locking the conversation.
                    $currentSender = User::query()->whereKey($sender->id)->lockForUpdate()->firstOrFail();
                    $current = Conversation::query()->whereKey($conversation->id)->lockForUpdate()->firstOrFail();
                    if ($admin) {
                        abort_unless($currentSender->role === 'admin' && $current->isSupport(), 403);
                    } else {
                        abort_unless($current->includes($currentSender), 404);
                        abort_if($current->isDeletedFor($currentSender), 404);
                    }
                    abort_if($current->isLocked(), 423, $admin ? 'Диалог заблокирован.' : 'Диалог временно заблокирован поддержкой.');

                    $message = $current->messages()->create([
                        'sender_id' => $currentSender->id, 'body' => $body, 'image_path' => $path,
                    ]);
                    if (! $message->exists) {
                        throw new \RuntimeException('Message creation was cancelled.');
                    }
                    $current->last_message_at = now();
                    if ($admin) {
                        $current->buyer_deleted_at = null;
                    } else {
                        $other = $current->otherParticipant($currentSender);
                        if ($other->role === 'admin' && $current->admin_deleted_at) {
                            $current->admin_deleted_at = null;
                        } elseif ($column = $current->deletedColumnFor($other)) {
                            $current->{$column} = null;
                        }
                    }
                    $current->save();
                    $conversation->setRawAttributes($current->getAttributes(), true);

                    if ($admin) {
                        DB::afterCommit(fn () => app(SupportReplyNotificationService::class)->send($message));
                    }
                    return $message;
                });
            } catch (\Throwable $exception) {
                $files->handleFailure();
                throw $exception;
            }
        });
    }
}
