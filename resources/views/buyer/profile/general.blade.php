@extends('buyer.profile')

@section('profile_content')
<section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-2">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <i class="ri-user-line text-indigo-500 text-xl"></i>
            Личная информация
        </h2>
        <span class="text-xs text-gray-400">
            Обновлено: {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}
        </span>
    </div>

    <form method="POST" action="{{ route('buyer.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="flex flex-col sm:flex-row items-center gap-6">
            <div class="relative shrink-0">
                <img src="{{ Auth::user()->avatar_url }}"
                     class="w-24 h-24 rounded-full border border-gray-200 shadow-sm object-cover" />

                <label class="absolute bottom-0 right-0 bg-indigo-600 text-white text-xs px-2 py-1 rounded-md cursor-pointer hover:bg-indigo-700 transition">
                    Изменить
                    <input type="file" name="avatar" class="hidden" accept="image/*">
                </label>
            </div>

            <div class="flex-1 w-full">
                <label class="block text-sm font-medium text-gray-700 mb-1">Имя пользователя</label>
                <input type="text" name="name"
                       value="{{ old('name', Auth::user()->name) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>

                <div class="relative">
                    <input type="email" name="email"
                           value="{{ Auth::user()->email }}"
                           class="w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <i class="ri-mail-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>

                @if (!Auth::user()->hasVerifiedEmail())
                    <p class="text-xs text-red-500 mt-1 flex items-center gap-1">
                        <i class="ri-error-warning-line"></i> Email не подтверждён
                    </p>
                @else
                    <p class="text-xs text-green-600 mt-1 flex items-center gap-1">
                        <i class="ri-checkbox-circle-line"></i> Email подтверждён
                    </p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
                <input type="text" name="phone"
                       value="{{ Auth::user()->phone }}"
                       placeholder="+373 777 77 777"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        <div class="flex justify-end border-t border-gray-100 pt-4">
            <button class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium shadow-sm flex gap-2">
                <i class="ri-save-line"></i> Сохранить изменения25
            </button>
        </div>
    </form>

</section>
@endsection
