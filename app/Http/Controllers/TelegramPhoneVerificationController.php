<?php

namespace App\Http\Controllers;

use App\Models\TelegramPhoneVerification;
use App\Services\TelegramPhoneVerificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class TelegramPhoneVerificationController extends Controller
{
    public function store(Request $request, TelegramPhoneVerificationService $service)
    {
        abort_unless(TelegramPhoneVerificationService::enabled(), 404);
        abort_unless(in_array($request->user()->role, ['buyer', 'seller'], true), 403);
        try {
            // Direct redirect: no raw token in a database-backed flash/session store.
            return redirect()->away($service->create($request->user()))
                ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            return back()->withErrors(['phone' => 'Не удалось создать заявку. Повторите позже.']);
        }
    }

    public function status(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['buyer', 'seller'], true), 403);
        $user = $request->user()->fresh();
        abort_unless($user, 404);
        $latest = TelegramPhoneVerification::where('user_id', $user->id)->latest('id')->first();
        $status = $user->phone_verified_at ? 'verified'
            : ($latest?->pending() && $latest->phone === $user->phone ? 'pending' : 'inactive');

        return response()->json(['status' => $status])->header('Cache-Control', 'no-store');
    }
}
