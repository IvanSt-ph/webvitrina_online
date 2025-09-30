<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

public function cabinet()
{
    $user = auth()->user();

    // Если гость — показываем заглушку
    if (!$user) {
        return view('profile.guest-cabinet');
    }

    // Если продавец
    if ($user->isSeller()) {
        // Все заказы, в которых есть товары продавца
        $orders = \App\Models\Order::whereHas('items.product', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->latest()->paginate(10);

        return view('seller.cabinet', compact('user', 'orders'));
    }

    // Кабинет покупателя
    return view('profile.buyer-cabinet', compact('user'));
}


}
