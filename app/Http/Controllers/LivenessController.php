<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\JsonResponse;

class LivenessController
{
    public function __invoke(): JsonResponse
    {
        try {
            Event::dispatch(new DiagnosingHealth);

            return response()->json(['status' => 'ok'])->header('Cache-Control', 'no-store');
        } catch (\Throwable) {
            return response()->json(['status' => 'unavailable'], 503)->header('Cache-Control', 'no-store');
        }
    }
}
