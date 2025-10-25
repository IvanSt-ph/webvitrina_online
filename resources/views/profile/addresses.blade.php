<x-buyer-layout title="Личный кабинет">
  <div class="space-y-8">

    <!-- Заголовок -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-gray-800">📬 Мои адреса доставки</h1>
        <p class="text-sm text-gray-500 mt-1">Добавьте или измените адреса для оформления заказов</p>
      </div>

      <!-- Кнопка добавления -->
<button 
  x-data 
  @click="$dispatch('open-modal', 'addAddress')"
  class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
  + Добавить адрес
</button>

    </div>

    <!-- Список адресов -->
    <div class="space-y-4">
      @forelse ($addresses as $address)
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 hover:shadow-md transition flex flex-col sm:flex-row sm:items-center sm:justify-between">

          <!-- Левая часть -->
          <div class="text-gray-700">
            <div class="font-semibold text-lg text-gray-900 flex items-center gap-2">
              {{ $address->city ?? '—' }}, {{ $address->street ?? '' }} {{ $address->house ?? '' }}
              @if ($address->is_default)
                <span class="text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full">Основной</span>
              @endif
            </div>
            <div class="text-sm text-gray-500 mt-1">
              {{ $address->country ?? '' }} • {{ $address->postal_code ?? 'Без индекса' }}
              @if ($address->apartment)
                • кв. {{ $address->apartment }}
              @endif
            </div>
            @if ($address->comment)
              <p class="text-xs text-gray-400 mt-1">💬 {{ $address->comment }}</p>
            @endif
          </div>

          <!-- Кнопки -->
          <div class="flex gap-2 mt-4 sm:mt-0">
            @unless ($address->is_default)
              <form method="POST" action="{{ route('addresses.default', $address) }}">
                @csrf
                <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                  Сделать основным
                </button>
              </form>
            @endunless

            <form method="POST" action="{{ route('addresses.destroy', $address) }}">
              @csrf
              @method('DELETE')
              <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                Удалить
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="text-center py-12 text-gray-500 bg-white rounded-xl border border-gray-100 shadow-sm">
          <i class="ri-map-pin-line text-4xl text-gray-400 mb-3 block"></i>
          <p>У вас пока нет сохранённых адресов</p>
        </div>
      @endforelse
    </div>

    <!-- Модальное окно: добавление адреса -->
    <x-modal name="addAddress">
      <form method="POST" action="{{ route('addresses.store') }}" class="p-6 space-y-4">
        @csrf
        <h2 class="text-lg font-semibold text-gray-800 mb-4">➕ Добавить адрес доставки</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-600">Страна</label>
            <input type="text" name="country" required class="mt-1 w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Город</label>
            <input type="text" name="city" required class="mt-1 w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Улица</label>
            <input type="text" name="street" required class="mt-1 w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Дом</label>
            <input type="text" name="house" class="mt-1 w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Квартира</label>
            <input type="text" name="apartment" class="mt-1 w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Почтовый индекс</label>
            <input type="text" name="postal_code" class="mt-1 w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-600">Комментарий</label>
          <textarea name="comment" rows="2" class="mt-1 w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" name="is_default" id="is_default" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
          <label for="is_default" class="text-sm text-gray-700">Сделать основным</label>
        </div>

        <div class="flex justify-end mt-6">
          <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            Сохранить адрес
          </button>
        </div>
      </form>
    </x-modal>

  </div>
</x-app-layout>
