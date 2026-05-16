@extends('buyer.profile')

@section('profile_content')
<section class="bg-white border border-gray-100 rounded-xl sm:rounded-2xl shadow-sm p-3 sm:p-8 space-y-6">

    @if(session('status') === 'password-updated')
    <div class="p-4 mb-4 text-green-700 bg-green-100 rounded-lg shadow-sm">
        Пароль успешно изменён.
    </div>
@endif

@if($errors->updatePassword->any())
    <div class="p-4 mb-4 text-red-700 bg-red-100 rounded-lg shadow-sm">
        <ul class="list-disc list-inside">
            @foreach($errors->updatePassword->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


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
      x-data="{ showCurrent:false, showNew:false, showConfirm:false }">

    @csrf
    @method('PUT')

    <div class="grid sm:grid-cols-2 gap-6">

        @if(Auth::user()->hasLocalPassword())
            <!-- Текущий пароль -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Текущий пароль</label>
                <div class="relative">
                    <input :type="showCurrent ? 'text' : 'password'" name="current_password" required
                           class="w-full pr-10 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
                    <button type="button" @click="showCurrent = !showCurrent"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <i :class="showCurrent ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                </div>
            </div>
        @else
            <div class="sm:col-span-2 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                Вы входите через Google. Установите пароль, чтобы иметь резервный способ входа и подтверждать важные изменения.
            </div>
        @endif

        <!-- Новый пароль -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
            <div class="relative">
                <input :type="showNew ? 'text' : 'password'" name="password" required
                       class="w-full pr-10 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
                <button type="button" @click="showNew = !showNew"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <i :class="showNew ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                </button>
            </div>
        </div>

        <!-- Подтверждение нового пароля -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Подтверждение</label>
            <div class="relative">
                <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required
                       class="w-full pr-10 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
                <button type="button" @click="showConfirm = !showConfirm"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                </button>
            </div>
        </div>

    </div>

    <div class="flex justify-end border-t border-gray-100 pt-4">
        <x-action-button>
            <i class="ri-lock-password-line"></i>
            {{ Auth::user()->hasLocalPassword() ? 'Сменить пароль' : 'Установить пароль' }}
        </x-action-button>
    </div>

</form>


</section>
@endsection
