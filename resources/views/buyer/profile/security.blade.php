@extends('buyer.profile')

@section('profile_content')
<section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-6">

    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <i class="ri-shield-keyhole-line text-indigo-500"></i>
            Безопасность аккаунта
        </h2>
        <span class="text-xs text-gray-400">
            Последнее изменение: {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}
        </span>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-6 max-w-2xl"
          x-data="{ showNew:false, showConfirm:false }">

        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-6">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
                <div class="relative">
                    <input :type="showNew ? 'text' : 'password'" name="password"
                           class="w-full rounded-lg border-gray-300 shadow-sm pr-10 focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="button" @click="showNew = !showNew"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <i :class="showNew ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Подтверждение</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation"
                           class="w-full rounded-lg border-gray-300 shadow-sm pr-10 focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="button" @click="showConfirm = !showConfirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                </div>
            </div>

        </div>

        <div class="flex justify-end border-t border-gray-100 pt-4">
            <button class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium flex gap-2 shadow-sm">
                <i class="ri-lock-password-line"></i>
                Сменить пароль
            </button>
        </div>

    </form>

</section>
@endsection
