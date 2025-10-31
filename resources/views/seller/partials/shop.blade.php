<section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-6">
  <div class="flex items-center justify-between flex-wrap gap-2">
    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
      <i class="ri-store-2-line text-indigo-500"></i> Информация о магазине
    </h2>
    <span class="text-xs text-gray-400">Последнее обновление: {{ Auth::user()->shop?->updated_at?->diffForHumans() ?? '—' }}</span>
  </div>

  <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-6">
    @csrf
    @method('PATCH')

    {{-- Основные поля --}}
    <div class="grid sm:grid-cols-2 gap-6">
      {{-- Название магазина --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Название магазина</label>
        <input type="text" name="name"
               value="{{ old('name', Auth::user()->shop?->name) }}"
               placeholder="Например: ТехноМаркет 24"
               maxlength="255"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
      </div>

      {{-- Город --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Город</label>
        <input type="text" name="city"
               value="{{ old('city', Auth::user()->shop?->city) }}"
               placeholder="Тирасполь"
               maxlength="255"
               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
      </div>
    </div>

    {{-- Телефон --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
      <div class="relative">
        <input type="tel" name="phone"
               value="{{ old('phone', Auth::user()->shop?->phone) }}"
               placeholder="+373 777 00 000"
               maxlength="50"
               inputmode="tel"
               class="w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
        <i class="ri-phone-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
      </div>
      <p class="text-xs text-gray-400 mt-1">Укажите телефон для клиентов — он будет отображаться на странице магазина.</p>
    </div>

    {{-- Описание --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Описание магазина</label>
      <textarea name="description"
                rows="4"
                maxlength="1000"
                placeholder="Кратко опишите ассортимент, преимущества, доставку или особые условия..."
                x-data="{ count: {{ strlen(old('description', Auth::user()->shop?->description ?? '')) }} }"
                @input="count = $event.target.value.length"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition resize-none">{{ old('description', Auth::user()->shop?->description) }}</textarea>
      <div class="flex justify-between text-xs text-gray-400 mt-1">
        <span>Максимум 1000 символов</span>
        <span x-text="count + ' / 1000'"></span>
      </div>
    </div>

    {{-- Кнопка --}}
    <div class="flex justify-end pt-4 border-t border-gray-100">
      <button type="submit"
              class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700
                     text-white rounded-lg text-sm font-medium shadow-sm flex items-center gap-2 transition">
        <i class="ri-save-3-line text-base"></i>
        Обновить данные магазина
      </button>
    </div>
  </form>
</section>

{{-- 💅 Доп. улучшения UX --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const phoneInput = document.querySelector('input[name="phone"]');
  if (phoneInput) {
    phoneInput.addEventListener('focus', () => {
      if (phoneInput.value.trim() === '') phoneInput.value = '+373 ';
    });
  }
});
</script>
