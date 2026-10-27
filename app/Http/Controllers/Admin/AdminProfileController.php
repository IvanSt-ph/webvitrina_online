<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\AdminActivityLogger;
use App\Services\PasswordSecurityService;
use App\Rules\ThrottledCurrentPassword;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AdminProfileController extends Controller
{
    public function __construct(
        private readonly AdminActivityLogger $activity,
        private readonly PasswordSecurityService $passwordSecurity
    ) {
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
        $passwordUser = DB::transaction(function () use ($request): ?User {
            $user = User::query()->whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            $sensitiveChange = $request->input('email') !== $user->email || $request->filled('password');

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'password' => ['nullable', 'confirmed', Password::defaults()],
                'current_password' => $user->hasLocalPassword() && $sensitiveChange
                    ? ['bail', 'required', 'string', new ThrottledCurrentPassword($user)]
                    : ['nullable'],
            ]);

            $before = $user->only(['name', 'email']);
            $passwordChanged = $request->filled('password');
            $emailChanged = $validated['email'] !== $user->email;
            $user->fill(['name' => $validated['name'], 'email' => $validated['email']]);
            $profileChanged = $user->isDirty(['name', 'email']) || $passwordChanged;

            if ($passwordChanged) {
                // Preserve rotation's rejection of a revoked authenticated snapshot.
                if (! hash_equals((string) $request->user()->getRawOriginal('password'), (string) $user->getAuthPassword())) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'login' => 'Учётные данные изменились. Войдите в аккаунт ещё раз.',
                    ]);
                }
                $this->passwordSecurity->rotate($user, $validated['password']);
            } else {
                $user->save();
            }

            if ($profileChanged) {
                $this->activity->log('profile.updated', $user, 'Администратор обновил собственный профиль.', [
                    'before' => $before,
                    'after' => $user->only(['name', 'email']),
                    'email_changed' => $emailChanged,
                    'password_changed' => $passwordChanged,
                ]);
            }

            return $passwordChanged ? $user : null;
        });

        if ($passwordUser !== null) {
            $request->session()->regenerate();
            $this->passwordSecurity->bindSession($request->session(), $passwordUser);
        }

        return redirect()
            ->route('admin.profile')
            ->with('success', 'Настройки аккаунта сохранены.');
    }
}
