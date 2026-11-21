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
        abort_unless($request->user()->role === 'seller', 403);
        try {
            // Direct redirect: no raw token in a database-backed flash/session store.
            return redirect()->away($service->create($request->user(), $request->session()->getId()))
                ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            return back()->withErrors(['phone' => 'Не удалось создать заявку. Повторите позже.']);
        }
    }

    public function status(Request $request)
    {
        abort_unless(TelegramPhoneVerificationService::enabled(), 404);
        abort_unless($request->user()->role === 'seller', 403);
        $user = $request->user()->fresh();
        abort_unless($user, 404);
        $latest = TelegramPhoneVerification::where('user_id', $user->id)
            ->where('session_hash', TelegramPhoneVerificationService::sessionHash($request->session()->getId()))
            ->latest('id')->first();
        $status = $user->phone_verified_at ? 'verified'
            : ($latest?->pending() && $latest->phone === $user->phone
                ? ($latest->contact_verified_at ? 'contact_verified' : 'pending') : 'inactive');

        $data = ['status' => $status];
        if ($status === 'contact_verified') {
            $data['verification_id'] = $latest->id;
        }
        return response()->json($data)->header('Cache-Control', 'no-store');
    }

    public function finalize(Request $request, TelegramPhoneVerificationService $service)
    {
        abort_unless(TelegramPhoneVerificationService::enabled(), 404);
        abort_unless($request->user()->role === 'seller', 403);
        $data = $request->validate(['verification_id' => ['required', 'integer', 'min:1']]);
        try {
            $service->finalize($request->user(), $request->session()->getId(), (int) $data['verification_id']);
        } catch (ValidationException|\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface|\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable) {
            // Database exceptions can include the session binding and phone in SQL bindings.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Не удалось завершить подтверждение. Повторите позже.'], 503);
            }
            return back()->withErrors(['phone' => 'Не удалось завершить подтверждение. Повторите позже.']);
        }

        return redirect()->route('profile.edit')->with('success', 'Личный телефон подтверждён.');
    }
}
