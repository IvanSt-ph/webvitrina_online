<section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-8">
  {{-- 🔐 Заголовок --}}
  <div class="flex items-center justify-between">
    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
      <i class="ri-shield-keyhole-line text-indigo-500"></i> Безопасность аккаунта
    </h2>
    <span class="text-xs text-gray-400">
      Последнее обновление: {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}
    </span>
  </div>

  {{-- ✅ Уведомление о смене пароля --}}
  @if (session('status') === 'password-updated')
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center gap-2">
      <i class="ri-lock-line text-lg"></i> Пароль успешно обновлён!
    </div>
  @endif

  {{-- 🧷 Смена пароля --}}
  <form method="POST" action="{{ route('password.update') }}" class="space-y-6 max-w-2xl">
    @csrf
    @method('PUT')

    <div class="grid sm:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Текущий пароль</label>
        <input type="password" name="current_password"
               placeholder="Введите текущий пароль"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('current_password') border-red-500 @enderror">
        @error('current_password')
          <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
        <input type="password" name="password"
               placeholder="Введите новый пароль"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('password') border-red-500 @enderror">
        @error('password')
          <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>
    </div>

    <div class="sm:w-1/2">
      <label class="block text-sm font-medium text-gray-700 mb-1">Подтверждение пароля</label>
      <input type="password" name="password_confirmation"
             placeholder="Повторите новый пароль"
             class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    </div>

    <div class="flex justify-end pt-4 border-t border-gray-100">
      <button type="submit"
              class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700
                     text-white rounded-lg text-sm font-medium shadow-sm flex items-center gap-2 transition">
        <i class="ri-lock-password-line text-base"></i>
        Сменить пароль
      </button>
    </div>
  </form>

  {{-- ⚠️ Удаление аккаунта --}}
  <div class="border-t border-gray-100 pt-8">
    <h3 class="text-lg font-semibold text-red-600 flex items-center gap-2">
      <i class="ri-delete-bin-6-line"></i> Удаление аккаунта
    </h3>
    <p class="text-sm text-gray-500 mt-1 max-w-md">
      При удалении аккаунта все данные будут безвозвратно стерты — включая товары, заказы, избранное и статистику.
    </p>

    <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 max-w-sm">
      @csrf
      @method('DELETE')
      <input type="password" name="password"
             placeholder="Введите пароль для подтверждения" required
             class="w-full mb-3 rounded-lg border-gray-300 shadow-sm focus:ring-red-500 focus:border-red-500">
      <button type="submit"
              onclick="return confirm('Вы уверены, что хотите удалить аккаунт безвозвратно?')"
              class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium shadow-sm transition flex items-center gap-2">
        <i class="ri-alert-line"></i> Удалить аккаунт
      </button>
    </form>
  </div>
</section>
