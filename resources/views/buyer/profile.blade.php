
{{-- resources/views/buyer/profile.blade.php --}}
<x-buyer-layout title="Настройки профиля">

  <div class="py-6 space-y-10 text-gray-800">

    <!-- 🔝 Заголовок -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Настройки профиля</h1>
        <p class="text-sm text-gray-500 mt-1">Редактируйте личные данные и пароль для входа</p>
      </div>
      <a href="{{ route('cabinet') }}"
         class="inline-flex items-center gap-2 px-4 py-2 text-sm border border-gray-200 rounded-xl hover:bg-indigo-50 hover:border-indigo-200 transition-all">
        <i class="ri-arrow-left-line text-indigo-500 text-lg"></i>
        <span>Назад в кабинет</span>
      </a>
    </div>


    <!-- ✅ Уведомления -->
    @if (session('status') === 'profile-updated')
      <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-center gap-2 shadow-sm">
        <i class="ri-check-line text-lg"></i>
        <span>Изменения успешно сохранены</span>
      </div>
    @endif

    @if ($errors->any())
      <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm shadow-sm">
        <strong class="block mb-1">Ошибка при сохранении:</strong>
        <ul class="list-disc ml-5 space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- 👤 Личные данные -->
    <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-8 space-y-6">
      <div class="flex items-center gap-2 mb-2">
        <i class="ri-user-3-line text-indigo-500 text-xl"></i>
        <h2 class="text-lg font-semibold text-gray-900">Личная информация</h2>
      </div>

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
            <label class="block text-sm font-medium text-gray-600 mb-1">Имя пользователя</label>
            <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}"
                   class="border-gray-200 rounded-xl w-full text-gray-800 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}"
                   class="border-gray-200 rounded-xl w-full text-gray-800 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Телефон</label>
            <input type="text" name="phone" value="{{ old('phone', Auth::user()->phone) }}"
                   placeholder="+373 777 77 777"
                   class="border-gray-200 rounded-xl w-full text-gray-800 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button type="submit"
                  class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium shadow-sm transition-all">
            💾 Сохранить изменения
          </button>
        </div>
      </form>
    </section>

    <!-- 🔒 Безопасность -->
    <section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-8 space-y-6">
      <div class="flex items-center gap-2 mb-2">
        <i class="ri-lock-line text-indigo-500 text-xl"></i>
        <h2 class="text-lg font-semibold text-gray-900">Безопасность</h2>
      </div>

      <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Новый пароль</label>
            <input type="password" name="password"
                   class="border-gray-200 rounded-xl w-full text-gray-800 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Подтверждение пароля</label>
            <input type="password" name="password_confirmation"
                   class="border-gray-200 rounded-xl w-full text-gray-800 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit"
                  class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium shadow-sm transition-all">
            🔑 Сменить пароль
          </button>
        </div>
      </form>
    </section>

  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-buyer-layout>
