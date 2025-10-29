<x-seller-layout title="Профиль продавца">

  <div class="max-w-6xl mx-auto px-6 py-10 space-y-10 text-gray-800">

    <!-- 🔝 Заголовок -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Профиль продавца</h1>
        <p class="text-sm text-gray-500 mt-1">Редактируйте данные компании, контакты и безопасность аккаунта</p>
      </div>
      <a href="{{ route('seller.products.index') }}"
         class="inline-flex items-center gap-2 px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition">
        <i class="ri-arrow-left-line text-gray-400 text-lg"></i>
        Вернуться к товарам
      </a>
    </div>

    <!-- ✅ Уведомления -->
    @if (session('status') === 'profile-updated')
      <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center gap-2">
        <i class="ri-check-line text-lg"></i>
        <span>Изменения успешно сохранены</span>
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
    <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-8 space-y-6">
      <h2 class="text-lg font-semibold text-gray-900">Основная информация</h2>

      <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="flex flex-col sm:flex-row items-center gap-6">
          <div class="relative">
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
                   class="border-gray-300 rounded-lg shadow-sm w-full focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}"
                   class="border-gray-300 rounded-lg shadow-sm w-full focus:ring-indigo-500 focus:border-indigo-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
            <input type="text" name="phone" value="{{ old('phone', Auth::user()->phone) }}"
                   placeholder="+373 777 77 777"
                   class="border-gray-300 rounded-lg shadow-sm w-full focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button type="submit"
                  class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            Сохранить изменения
          </button>
        </div>
      </form>
    </section>

    <!-- 🏬 Данные магазина -->
    <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-8 space-y-6">
      <h2 class="text-lg font-semibold text-gray-900">Информация о магазине</h2>

      <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название магазина</label>
            <input type="text" name="shop_name"
                   value="{{ old('shop_name', Auth::user()->shop_name) }}"
                   placeholder="Например: ТехноМаркет 24"
                   class="border-gray-300 rounded-lg shadow-sm w-full focus:ring-indigo-500 focus:border-indigo-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Город</label>
            <input type="text" name="city"
                   value="{{ old('city', Auth::user()->city) }}"
                   placeholder="Тирасполь"
                   class="border-gray-300 rounded-lg shadow-sm w-full focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Описание магазина</label>
          <textarea name="shop_description" rows="4"
                    placeholder="Кратко опишите ассортимент, преимущества или условия доставки"
                    class="border-gray-300 rounded-lg shadow-sm w-full focus:ring-indigo-500 focus:border-indigo-500">{{ old('shop_description', Auth::user()->shop_description) }}</textarea>
        </div>

        <div class="flex justify-end pt-4">
          <button type="submit"
                  class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            Обновить данные магазина
          </button>
        </div>
      </form>
    </section>

    <!-- 🔒 Безопасность -->
    <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-8 space-y-6">
      <h2 class="text-lg font-semibold text-gray-900">Безопасность аккаунта</h2>

      <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
            <input type="password" name="password"
                   class="border-gray-300 rounded-lg shadow-sm w-full focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Подтверждение пароля</label>
            <input type="password" name="password_confirmation"
                   class="border-gray-300 rounded-lg shadow-sm w-full focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit"
                  class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            Сменить пароль
          </button>
        </div>
      </form>
    </section>

    <!-- 🗑 Удаление аккаунта -->
    <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-8">
      <div class="flex items-start justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-lg font-semibold text-red-600">Удаление аккаунта</h2>
          <p class="text-sm text-gray-500 mt-1 max-w-md">
            При удалении аккаунта все данные будут безвозвратно удалены, включая товары, заказы и статистику.
          </p>
        </div>

        <form method="POST" action="{{ route('profile.destroy') }}">
          @csrf
          @method('DELETE')
          <button type="submit"
                  class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium shadow-sm transition"
                  onclick="return confirm('Вы уверены, что хотите удалить аккаунт?')">
            Удалить аккаунт
          </button>
        </form>
      </div>
    </section>

  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
  @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
