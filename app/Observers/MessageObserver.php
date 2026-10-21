<?php

namespace App\Observers;

use App\Models\Message;
use App\Services\ChatImageOperation;

class MessageObserver
{
    public function deleted(Message $message): void
    {
        ChatImageOperation::deleteAfterCommit($message->getConnection(), $message->image_path);
    }
}
