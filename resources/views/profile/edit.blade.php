<x-seller-layout title="Профиль продавца">

  <main x-data="{ tab: 'main' }"
        class="pt-2 pb-10 space-y-10 px-4 sm:px-6 lg:px-8 text-gray-800">

    <!-- 🔝 Заголовок -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 flex items-center gap-2">
          <i class="ri-user-settings-line text-indigo-600"></i>
          Профиль продавца
        </h1>
        <p class="text-sm text-gray-500 mt-1">Редактируйте данные компании, контакты и безопасность аккаунта</p>
      </div>

      <a href="{{ route('seller.products.index') }}"
         class="inline-flex items-center gap-2 px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition">
        <i class="ri-arrow-left-line text-gray-400 text-lg"></i>
        Вернуться к товарам
      </a>
    </div>

    <!-- 🔖 Вкладки -->
    <div class="flex border-b border-gray-200 overflow-x-auto">
      <button @click="tab = 'main'"
              :class="tab === 'main' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600'"
              class="px-4 py-2 text-sm font-medium whitespace-nowrap">
        Основная информация
      </button>
      <button @click="tab = 'shop'"
              :class="tab === 'shop' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600'"
              class="px-4 py-2 text-sm font-medium whitespace-nowrap">
        Информация о магазине
      </button>
      <button @click="tab = 'security'"
              :class="tab === 'security' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600'"
              class="px-4 py-2 text-sm font-medium whitespace-nowrap">
        Безопасность
      </button>
    </div>

    <!-- ✅ Уведомления -->
    @if (session('status'))
      @php
        $messages = [
          'profile-updated' => ['bg-green-50 border-green-200 text-green-700', 'ri-check-line', 'Личные данные успешно обновлены'],
          'shop-updated'    => ['bg-blue-50 border-blue-200 text-blue-700', 'ri-store-2-line', 'Информация о магазине обновлена'],
        ];
        [$classes, $icon, $text] = $messages[session('status')] ?? ['bg-gray-50 border-gray-200 text-gray-700', 'ri-information-line', 'Изменения сохранены'];
      @endphp
      <div class="flex items-center gap-2 p-4 rounded-lg border text-sm {{ $classes }}">
        <i class="{{ $icon }} text-lg"></i>
        <span>{{ $text }}</span>
      </div>
    @endif

    @if ($errors->any())
      <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        <strong class="block mb-1">Ошибка при сохранении:</strong>
        <ul class="list-disc ml-5 space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- 🧾 Основная информация -->
    <section x-show="tab === 'main'" x-transition
             class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-6">
      <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <i class="ri-account-circle-line text-indigo-500"></i> Основная информация
      </h2>

      <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="flex flex-col sm:flex-row items-center gap-6">
          <div class="relative shrink-0">
            <img src="{{ Auth::user()->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name ?? 'U') }}"
                 alt="avatar"
                 class="w-24 h-24 rounded-full border border-gray-200 shadow-sm object-cover">
            <label
              class="absolute bottom-0 right-0 bg-indigo-600 text-white text-xs px-2 py-1 rounded-md cursor-pointer hover:bg-indigo-700 transition">
              Изменить
              <input type="file" name="avatar" class="hidden">
            </label>
          </div>
          <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 mb-1">Имя пользователя</label>
            <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
            <input type="text" name="phone" value="{{ old('phone', Auth::user()->phone) }}"
                   placeholder="+373 777 77 777"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-100">
          <button type="submit"
                  class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            Сохранить изменения
          </button>
        </div>
      </form>
    </section>

    <!-- 🏬 Информация о магазине -->
    <section x-show="tab === 'shop'" x-transition
             class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-6">
      <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <i class="ri-store-2-line text-indigo-500"></i> Информация о магазине
      </h2>

      <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название магазина</label>
            <input type="text" name="shop_name"
                   value="{{ old('shop_name', Auth::user()->shop_name) }}"
                   placeholder="Например: ТехноМаркет 24"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Город</label>
            <input type="text" name="city"
                   value="{{ old('city', Auth::user()->city) }}"
                   placeholder="Тирасполь"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Описание магазина</label>
          <textarea name="shop_description" rows="4"
                    placeholder="Кратко опишите ассортимент, преимущества или условия доставки"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('shop_description', Auth::user()->shop_description) }}</textarea>
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-100">
          <button type="submit"
                  class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            Обновить данные магазина
          </button>
        </div>
      </form>
    </section>

    <!-- 🔒 Безопасность -->
    <section x-show="tab === 'security'" x-transition
             class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-6">
      <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <i class="ri-lock-password-line text-indigo-500"></i> Безопасность аккаунта
      </h2>

      <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
            <input type="password" name="password"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Подтверждение пароля</label>
            <input type="password" name="password_confirmation"
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-100">
          <button type="submit"
                  class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            Сменить пароль
          </button>
        </div>
      </form>

      <div class="border-t border-gray-100 pt-6">
        <h3 class="text-lg font-semibold text-red-600 flex items-center gap-2">
          <i class="ri-delete-bin-6-line"></i> Удаление аккаунта
        </h3>
        <p class="text-sm text-gray-500 mt-1 max-w-md">
          При удалении аккаунта все данные будут безвозвратно удалены, включая товары, заказы и статистику.
        </p>
        <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4">
          @csrf
          @method('DELETE')
          <button type="submit"
                  onclick="return confirm('Вы уверены, что хотите удалить аккаунт?')"
                  class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            Удалить аккаунт
          </button>
        </form>
      </div>
    </section>
  </main>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
  @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
