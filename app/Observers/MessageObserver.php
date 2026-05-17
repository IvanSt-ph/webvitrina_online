<?php

namespace App\Observers;

use App\Models\Message;
use Illuminate\Support\Facades\Storage;

class MessageObserver
{
    public function deleted(Message $message): void
    {
        if ($message->image_path && Storage::disk('local')->exists($message->image_path)) {
            Storage::disk('local')->delete($message->image_path);
        }
    }
}
