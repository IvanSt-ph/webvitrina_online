<section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-6">

  {{-- 🔹 Заголовок --}}
  <div class="flex items-center justify-between flex-wrap gap-2">
    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
      <i class="ri-account-circle-line text-indigo-500"></i> Профиль пользователя
    </h2>
    <span class="text-xs text-gray-400">
      Последнее обновление: {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}
    </span>
  </div>

  {{-- ✅ Уведомление об успешном сохранении --}}
  @if (session('status') === 'profile-updated')
    <div class="p-4 mb-2 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center gap-2">
      <i class="ri-check-line text-lg"></i> Профиль успешно обновлён!
    </div>
  @endif

  {{-- 🔹 Основная форма --}}
  <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PATCH')

    {{-- 🧍‍♂️ Аватар + Имя --}}
    <div class="flex flex-col sm:flex-row items-center gap-6">
      <div class="relative shrink-0">
        <img src="{{ Auth::user()->avatar_url }}"
             alt="avatar"
             class="w-24 h-24 rounded-full border border-gray-200 shadow-sm object-cover transition-transform duration-200 hover:scale-105">
        <label
          class="absolute bottom-0 right-0 bg-indigo-600 text-white text-xs px-2 py-1 rounded-md cursor-pointer hover:bg-indigo-700 transition">
          Изменить
          <input type="file" name="avatar" class="hidden" accept="image/*" aria-label="Выбрать аватар">
        </label>
      </div>

      <div class="flex-1 w-full">
        <label class="block text-sm font-medium text-gray-700 mb-1">Имя пользователя</label>
        <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}"
               maxlength="255"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition @error('name') border-red-500 @enderror">
        @error('name')
          <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>
    </div>

    {{-- ✉️ Email + Статус подтверждения --}}
    <div class="grid sm:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <div class="relative">
          <input type="email" name="email"
                 value="{{ old('email', Auth::user()->email) }}"
                 maxlength="255"
                 class="w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition @error('email') border-red-500 @enderror">
          <i class="ri-mail-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
        @error('email')
          <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror

        @if (Auth::user()->hasVerifiedEmail())
          <p class="text-xs text-green-600 mt-1 flex items-center gap-1">
            <i class="ri-checkbox-circle-line"></i> Email подтверждён
          </p>
        @else
          <p class="text-xs text-red-500 mt-1 flex items-center gap-1">
            <i class="ri-error-warning-line"></i> Email не подтверждён
          </p>
        @endif
      </div>

      {{-- 📅 Дата регистрации --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Дата регистрации</label>
        <input type="text"
               disabled
               value="{{ Auth::user()->created_at?->format('d.m.Y') ?? '—' }}"
               class="w-full bg-gray-50 rounded-lg border-gray-200 text-gray-500 shadow-sm">
      </div>
    </div>

    {{-- 🏪 Если магазин создан --}}
    @if (Auth::user()->shop)
      <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4 text-sm text-indigo-800 flex items-center gap-2">
        <i class="ri-store-2-line text-indigo-500 text-lg"></i>
        Магазин <strong>{{ Auth::user()->shop->name ?? 'Без названия' }}</strong>
        создан {{ Auth::user()->shop->created_at?->diffForHumans() ?? 'недавно' }}.
      </div>
    @endif

    {{-- 💾 Кнопка --}}
    <div class="flex justify-end pt-4 border-t border-gray-100">
<button type="submit"
        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white 
               rounded-xl text-sm font-medium shadow-sm hover:shadow-md 
               flex items-center gap-2 transition-all duration-200">
  <i class="ri-save-line text-base"></i>
  Сохранить
</button>

    </div>
  </form>

  @if (!Auth::user()->hasVerifiedEmail())
  <form method="POST" action="{{ route('verification.send') }}" class="mt-3">
    @csrf
    <button type="submit"
            class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-xs 
                   rounded-lg shadow-sm flex items-center gap-2 transition">
      <i class="ri-mail-send-line"></i>
      Отправить письмо подтверждения
    </button>
  </form>

  @if (session('status') === 'verification-link-sent')
    <p class="text-xs text-green-600 mt-2 flex items-center gap-1">
        <i class="ri-check-double-line"></i>
        Письмо отправлено! Проверь свою почту.
    </p>
@endif
@endif
</section>
