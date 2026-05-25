<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminProfileController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function edit()
    {
        $user = auth()->user();

        return view('admin.profile', [
            'user' => $user,
            'recentActivity' => AdminActivityLog::query()
                ->where('admin_id', $user->id)
                ->latest()
                ->limit(5)
                ->get(),
            'activityCount' => AdminActivityLog::where('admin_id', $user->id)->count(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $sensitiveChange = $request->input('email') !== $user->email || $request->filled('password');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'current_password' => $user->hasLocalPassword() && $sensitiveChange
                ? ['required', 'current_password']
                : ['nullable'],
        ]);

        $before = $user->only(['name', 'email']);
        $passwordChanged = $request->filled('password');
        $emailChanged = $validated['email'] !== $user->email;

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $profileChanged = $user->isDirty(['name', 'email']) || $passwordChanged;

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if ($passwordChanged) {
            $user->password = Hash::make($validated['password']);
            $user->password_set_at = now();
        }

        $user->save();

        if ($profileChanged) {
            $this->activity->log('profile.updated', $user, 'Администратор обновил собственный профиль.', [
                'before' => $before,
                'after' => $user->only(['name', 'email']),
                'email_changed' => $emailChanged,
                'password_changed' => $passwordChanged,
            ]);
        }

        return redirect()
            ->route('admin.profile')
            ->with('success', 'Настройки аккаунта сохранены.');
    }
}
