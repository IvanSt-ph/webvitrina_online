<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Auth;

class PhoneVerificationController extends Controller
{
    protected function twilio()
    {
        return new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    public function send()
    {
        $user = Auth::user();

        $this->twilio()
            ->verify
            ->v2
            ->services(config('services.twilio.verify_sid'))
            ->verifications
            ->create($user->phone, 'sms');

        return back()->with('phone_sent', true);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $user = Auth::user();

        $check = $this->twilio()
            ->verify
            ->v2
            ->services(config('services.twilio.verify_sid'))
            ->verificationChecks
            ->create([
                'to' => $user->phone,
                'code' => $request->code
            ]);

        if ($check->status === 'approved') {
            $user->phone_verified_at = now();
            $user->save();

            return back()->with('success', 'Телефон подтверждён');
        }

        return back()->withErrors(['code' => 'Неверный код']);
    }
}
