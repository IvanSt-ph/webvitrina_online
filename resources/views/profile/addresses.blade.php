<x-buyer-layout title="Личный кабинет">
  <div class="space-y-6 sm:space-y-8">

    <!-- Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
          <i class="ri-map-pin-line text-xl"></i>
        </div>
        <div>
        <h1 class="text-2xl font-semibold text-gray-900">Мои адреса доставки</h1>
        <p class="text-sm text-gray-500 mt-1">Добавьте или измените адреса для оформления заказов</p>
        </div>
      </div>

      <!-- Кнопка добавления -->
      <x-action-button type="button" x-data x-on:click="$dispatch('open-modal', 'addAddress')">
        <i class="ri-add-line text-lg"></i>
        Добавить адрес
      </x-action-button>

    </div>

    <!-- Список адресов -->
    <div class="space-y-4">
      @forelse ($addresses as $address)
        <div class="bg-white border border-gray-200 rounded-xl sm:rounded-2xl shadow-sm p-4 sm:p-5 hover:shadow-md transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

          <!-- Левая часть -->
          <div class="text-gray-700 flex items-start gap-4 min-w-0">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
              <i class="ri-home-4-line text-lg"></i>
            </div>
            <div class="min-w-0">
              <div class="font-semibold text-lg text-gray-900 flex flex-wrap items-center gap-2">
                <span>{{ $address->city ?? '—' }}, {{ $address->street ?? '' }} {{ $address->house ?? '' }}</span>
                @if ($address->is_default)
                  <span class="inline-flex items-center gap-1 text-xs bg-indigo-50 text-indigo-700 border border-indigo-100 px-2 py-1 rounded-full">
                    <i class="ri-star-smile-line"></i>
                    Основной
                  </span>
                @endif
              </div>
              <div class="text-sm text-gray-500 mt-1">
                {{ $address->country ?? '' }} • {{ $address->postal_code ?? 'Без индекса' }}
                @if ($address->apartment)
                  • кв. {{ $address->apartment }}
                @endif
                @if ($address->entrance)
                  • подъезд {{ $address->entrance }}
                @endif
              </div>
              @if ($address->comment)
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                  <i class="ri-chat-1-line"></i>
                  {{ $address->comment }}
                </p>
              @endif
            </div>
          </div>

          <!-- Кнопки -->
          <div class="flex flex-wrap gap-2 sm:justify-end">
            @unless ($address->is_default)
              <form method="POST" action="{{ route('addresses.default', $address) }}">
                @csrf
                <button type="submit" class="h-10 px-4 rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-sm font-semibold transition flex items-center gap-2">
                  <i class="ri-star-line"></i>
                  Сделать основным
                </button>
              </form>
            @endunless

            <form method="POST" action="{{ route('addresses.destroy', $address) }}">
              @csrf
              @method('DELETE')
              <button type="submit" class="h-10 px-4 rounded-xl border border-rose-100 bg-rose-50 text-rose-600 hover:bg-rose-100 text-sm font-semibold transition flex items-center gap-2">
                <i class="ri-delete-bin-line"></i>
                Удалить
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="text-center py-12 px-4 text-gray-500 bg-white rounded-xl sm:rounded-2xl border border-gray-100 shadow-sm">
          <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mb-4">
            <i class="ri-map-pin-line text-3xl"></i>
          </div>
          <p class="text-sm font-medium text-gray-700">У вас пока нет сохранённых адресов</p>
          <p class="text-xs text-gray-400 mt-1">Добавьте адрес, чтобы быстрее оформлять заказы.</p>
        </div>
      @endforelse
    </div>

    <!-- Модальное окно: добавление адреса -->
    <x-modal name="addAddress">
      <form method="POST" action="{{ route('addresses.store') }}" class="p-4 sm:p-6 space-y-5">
        @csrf
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
            <i class="ri-add-line text-xl"></i>
          </div>
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Добавить адрес доставки</h2>
            <p class="text-xs text-gray-500 mt-0.5">Адрес можно будет выбрать при оформлении заказа</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-600">Страна</label>
            <input type="text" name="country" required class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Город</label>
            <input type="text" name="city" required class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Улица</label>
            <input type="text" name="street" required class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Дом</label>
            <input type="text" name="house" class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Квартира</label>
            <input type="text" name="apartment" class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600">Почтовый индекс</label>
            <input type="text" name="postal_code" class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-600">Комментарий</label>
          <textarea name="comment" rows="2" class="mt-1 w-full px-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none resize-none"></textarea>
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" name="is_default" id="is_default" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
          <label for="is_default" class="text-sm text-gray-700">Сделать основным</label>
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-100">
          <x-action-button>
            <i class="ri-save-line"></i>
            Сохранить адрес
          </x-action-button>
        </div>
      </form>
    </x-modal>

  </div>
  
</x-buyer-layout>
