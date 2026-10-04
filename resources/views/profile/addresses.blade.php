<x-buyer-layout title="Адреса доставки">
  <div class="addresses-mobile-safe min-h-screen overflow-x-hidden bg-white pb-24 text-neutral-800 md:pb-0">
    <header class="border-b border-neutral-200 bg-white">
      <div class="flex w-full flex-col gap-5 px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div class="min-w-0">
          <div class="mb-2 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
            <i class="ri-map-pin-line text-base" aria-hidden="true"></i>
            Доставка
          </div>
          <h1 class="text-2xl font-semibold tracking-tight text-neutral-900 sm:text-[28px]">Мои адреса</h1>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-neutral-500">Сохраняйте адреса и выбирайте основной, чтобы быстрее оформлять заказы.</p>
        </div>
        <x-action-button type="button" class="shrink-0" x-data x-on:click="$dispatch('open-modal', 'addAddress')">
          <i class="ri-add-line text-lg" aria-hidden="true"></i>
          Добавить адрес
        </x-action-button>
      </div>
    </header>

    <main class="w-full space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12">
      <section class="grid overflow-hidden rounded-2xl border border-brand-100 bg-brand-50/50 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="flex flex-col justify-center p-5 sm:p-6 lg:p-7">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm ring-1 ring-brand-100">
            <i class="ri-home-heart-line text-xl" aria-hidden="true"></i>
          </div>
          <h2 class="mt-4 text-xl font-semibold tracking-tight text-neutral-900 sm:text-2xl">Доставка без лишних шагов</h2>
          <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">Добавьте дом, работу или адрес близкого человека. Основной адрес будет предложен первым при оформлении заказа.</p>
        </div>
        <div class="flex items-center justify-between gap-5 border-t border-brand-100 bg-white/60 p-5 sm:p-6 lg:border-l lg:border-t-0">
          <div>
            <p class="text-3xl font-semibold tracking-tight text-brand-600">{{ $addresses->count() }}</p>
            <p class="mt-2 text-sm font-medium text-neutral-500">Сохранено адресов</p>
          </div>
          <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand-50 text-3xl text-brand-600 ring-8 ring-white">
            <i class="ri-map-2-line" aria-hidden="true"></i>
          </div>
        </div>
      </section>

      @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700" role="status">
          <i class="ri-checkbox-circle-fill text-lg" aria-hidden="true"></i>
          <span>{{ session('success') }}</span>
        </div>
      @endif

      <div class="border-b border-neutral-200 pb-4">
        <h2 class="text-lg font-semibold text-neutral-900">Сохранённые адреса</h2>
        <p class="mt-1 text-sm text-neutral-500">Редактируйте данные или назначьте адрес основным.</p>
      </div>

      <section class="grid gap-5 xl:grid-cols-2" aria-label="Список адресов">
        @forelse ($addresses as $address)
          <article class="group flex min-w-0 flex-col rounded-2xl border bg-white transition duration-300 {{ $address->is_default ? 'border-brand-200 ring-1 ring-brand-100' : 'border-neutral-200 hover:border-brand-200' }} hover:shadow-xl hover:shadow-brand-100/40">
            <div class="flex flex-1 items-start gap-4 p-5 sm:p-6">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $address->is_default ? 'bg-brand-500 text-white shadow-md shadow-brand-200' : 'bg-brand-50 text-brand-600' }}">
                <i class="{{ $address->is_default ? 'ri-home-heart-fill' : 'ri-home-4-line' }} text-xl" aria-hidden="true"></i>
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start gap-2">
                  <h3 class="min-w-0 break-words text-lg font-semibold leading-6 text-neutral-900" style="overflow-wrap:anywhere;">{{ $address->city ?? '—' }}, {{ $address->street ?? '' }} {{ $address->house ?? '' }}</h3>
                  @if ($address->is_default)
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full border border-brand-100 bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700"><i class="ri-star-fill" aria-hidden="true"></i>Основной</span>
                  @endif
                </div>
                <p class="mt-2 break-words text-sm leading-6 text-neutral-500" style="overflow-wrap:anywhere;">
                  {{ $address->country ?? '' }} · {{ $address->postal_code ?? 'Без индекса' }}
                  @if ($address->apartment) · кв. {{ $address->apartment }} @endif
                  @if ($address->entrance) · подъезд {{ $address->entrance }} @endif
                </p>
                @if ($address->comment)
                  <div class="mt-4 flex items-start gap-2 rounded-xl bg-neutral-50 px-3 py-2.5 text-sm leading-5 text-neutral-600">
                    <i class="ri-chat-1-line mt-0.5 shrink-0 text-neutral-400" aria-hidden="true"></i>
                    <span class="min-w-0 break-words" style="overflow-wrap:anywhere;">{{ $address->comment }}</span>
                  </div>
                @endif
              </div>
            </div>

            <div class="flex flex-col gap-2 border-t border-neutral-100 px-5 py-4 sm:flex-row sm:flex-wrap sm:justify-end sm:px-6">
              @unless ($address->is_default)
                <form method="POST" action="{{ route('addresses.default', $address) }}">
                  @csrf
                  <x-secondary-action type="submit" size="sm" full>
                    <i class="ri-star-line" aria-hidden="true"></i>
                    Сделать основным
                  </x-secondary-action>
                </form>
              @endunless
              <x-secondary-action type="button" size="sm" x-data x-on:click="$dispatch('open-modal', 'editAddress{{ $address->id }}')">
                <i class="ri-pencil-line" aria-hidden="true"></i>
                Изменить
              </x-secondary-action>
              <form method="POST" action="{{ route('addresses.destroy', $address) }}">
                @csrf @method('DELETE')
                <x-danger-action type="submit" size="sm" full>
                  <i class="ri-delete-bin-line" aria-hidden="true"></i>
                  Удалить
                </x-danger-action>
              </form>
            </div>
          </article>

          <x-modal name="editAddress{{ $address->id }}">
            <form method="POST" action="{{ route('addresses.update', $address) }}" class="space-y-6 p-4 sm:p-6">
              @csrf @method('PUT')
              <div class="flex items-center gap-3 border-b border-neutral-100 pb-5">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500 text-white shadow-md shadow-brand-200"><i class="ri-pencil-line text-xl"></i></div>
                <div><h2 class="text-lg font-semibold text-neutral-900">Изменить адрес</h2><p class="mt-0.5 text-xs text-neutral-500">Обновите данные доставки</p></div>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><label class="block text-sm font-medium text-neutral-600">Страна</label><x-form-input type="text" name="country" value="{{ old('country', $address->country) }}" required class="mt-1" /></div>
                <div><label class="block text-sm font-medium text-neutral-600">Город</label><x-form-input type="text" name="city" value="{{ old('city', $address->city) }}" required class="mt-1" /></div>
                <div><label class="block text-sm font-medium text-neutral-600">Улица</label><x-form-input type="text" name="street" value="{{ old('street', $address->street) }}" required class="mt-1" /></div>
                <div><label class="block text-sm font-medium text-neutral-600">Дом</label><x-form-input type="text" name="house" value="{{ old('house', $address->house) }}" class="mt-1" /></div>
                <div><label class="block text-sm font-medium text-neutral-600">Подъезд</label><x-form-input type="text" name="entrance" value="{{ old('entrance', $address->entrance) }}" class="mt-1" /></div>
                <div><label class="block text-sm font-medium text-neutral-600">Квартира</label><x-form-input type="text" name="apartment" value="{{ old('apartment', $address->apartment) }}" class="mt-1" /></div>
                <div><label class="block text-sm font-medium text-neutral-600">Почтовый индекс</label><x-form-input type="text" name="postal_code" value="{{ old('postal_code', $address->postal_code) }}" class="mt-1" /></div>
              </div>
              <div><label class="block text-sm font-medium text-neutral-600">Комментарий</label><x-form-input textarea name="comment" rows="2" class="mt-1">{{ old('comment', $address->comment) }}</x-form-input></div>
              <label class="flex w-fit cursor-pointer items-center gap-3 rounded-xl bg-neutral-50 px-4 py-3">
                <input type="checkbox" name="is_default" value="1" class="rounded border-neutral-300 text-brand-600 focus:ring-brand-500" @checked($address->is_default)>
                <span class="text-sm font-medium text-neutral-700">Сделать основным</span>
              </label>
              <div class="flex flex-col-reverse gap-2 border-t border-neutral-100 pt-4 sm:flex-row sm:justify-end">
                <x-secondary-action type="button" x-on:click="$dispatch('close-modal', 'editAddress{{ $address->id }}')">Отмена</x-secondary-action>
                <x-action-button type="submit"><i class="ri-save-line"></i>Сохранить</x-action-button>
              </div>
            </form>
          </x-modal>
        @empty
          <div class="rounded-3xl border border-dashed border-neutral-300 bg-neutral-50/60 px-6 py-16 text-center xl:col-span-2 sm:py-20">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-8 ring-white"><i class="ri-map-pin-line text-3xl"></i></div>
            <h2 class="mt-6 text-xl font-semibold text-neutral-900">У вас пока нет сохранённых адресов</h2>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-neutral-500">Добавьте адрес, чтобы быстрее оформлять следующие заказы.</p>
            <div class="mt-7 flex justify-center">
              <x-action-button type="button" x-data x-on:click="$dispatch('open-modal', 'addAddress')"><i class="ri-add-line"></i>Добавить адрес</x-action-button>
            </div>
          </div>
        @endforelse
      </section>
    </main>

    <x-modal name="addAddress">
      <form method="POST" action="{{ route('addresses.store') }}" class="space-y-6 p-4 sm:p-6">
        @csrf
        <div class="flex items-center gap-3 border-b border-neutral-100 pb-5">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500 text-white shadow-md shadow-brand-200"><i class="ri-add-line text-xl"></i></div>
          <div><h2 class="text-lg font-semibold text-neutral-900">Добавить адрес доставки</h2><p class="mt-0.5 text-xs text-neutral-500">Его можно будет выбрать при оформлении заказа</p></div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div><label class="block text-sm font-medium text-neutral-600">Страна</label><x-form-input type="text" name="country" value="{{ old('country') }}" required class="mt-1" /></div>
          <div><label class="block text-sm font-medium text-neutral-600">Город</label><x-form-input type="text" name="city" value="{{ old('city') }}" required class="mt-1" /></div>
          <div><label class="block text-sm font-medium text-neutral-600">Улица</label><x-form-input type="text" name="street" value="{{ old('street') }}" required class="mt-1" /></div>
          <div><label class="block text-sm font-medium text-neutral-600">Дом</label><x-form-input type="text" name="house" value="{{ old('house') }}" class="mt-1" /></div>
          <div><label class="block text-sm font-medium text-neutral-600">Подъезд</label><x-form-input type="text" name="entrance" value="{{ old('entrance') }}" class="mt-1" /></div>
          <div><label class="block text-sm font-medium text-neutral-600">Квартира</label><x-form-input type="text" name="apartment" value="{{ old('apartment') }}" class="mt-1" /></div>
          <div><label class="block text-sm font-medium text-neutral-600">Почтовый индекс</label><x-form-input type="text" name="postal_code" value="{{ old('postal_code') }}" class="mt-1" /></div>
        </div>
        <div><label class="block text-sm font-medium text-neutral-600">Комментарий</label><x-form-input textarea name="comment" rows="2" class="mt-1">{{ old('comment') }}</x-form-input></div>
        <label class="flex w-fit cursor-pointer items-center gap-3 rounded-xl bg-neutral-50 px-4 py-3">
          <input type="checkbox" name="is_default" value="1" class="rounded border-neutral-300 text-brand-600 focus:ring-brand-500" @checked(old('is_default'))>
          <span class="text-sm font-medium text-neutral-700">Сделать основным</span>
        </label>
        <div class="flex flex-col-reverse gap-2 border-t border-neutral-100 pt-4 sm:flex-row sm:justify-end">
          <x-secondary-action type="button" x-on:click="$dispatch('close-modal', 'addAddress')">Отмена</x-secondary-action>
          <x-action-button type="submit"><i class="ri-save-line"></i>Сохранить адрес</x-action-button>
        </div>
      </form>
    </x-modal>
  </div>

  @include('layouts.mobile-bottom-nav')

  <style>
    .addresses-mobile-safe, .addresses-mobile-safe * { box-sizing: border-box; }
    .addresses-mobile-safe { max-width: 100vw; }
  </style>
</x-buyer-layout>
