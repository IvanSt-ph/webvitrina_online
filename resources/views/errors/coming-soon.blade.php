<x-app-layout title="Раздел в разработке" :hideHeader="true">
  <div class="min-h-screen flex flex-col items-center justify-center bg-white text-gray-700">

    <div class="max-w-lg w-full text-center px-6 py-20">

      <!-- 🧩 Иллюстрация -->
      <div class="relative mb-10">
        <div class="absolute inset-0 blur-3xl bg-indigo-100/50 rounded-full -z-10 scale-125"></div>
        <img src="{{ asset('images/soon-illustration.svg') }}" 
             alt="В разработке" 
             class="w-56 h-56 mx-auto opacity-95">
      </div>

      <!-- 🏗 Заголовок -->
      <h1 class="text-3xl font-bold text-gray-900 tracking-tight">
        Раздел в разработке
      </h1>

      <!-- 📜 Подзаголовок -->
      <p class="mt-3 text-base text-gray-500 leading-relaxed">
        Мы уже работаем над этим функционалом.<br>
        Скоро вы сможете воспользоваться всеми возможностями этого раздела.
      </p>

      <!-- 🔘 Кнопки -->
      <div class="mt-10 flex flex-col sm:flex-row justify-center gap-3">
        <a href="{{ url()->previous() }}"
           class="inline-flex justify-center items-center gap-2 px-5 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
          <i class="ri-arrow-left-line text-lg"></i> Назад
        </a>

        <a href="{{ route('home') }}"
           class="inline-flex justify-center items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
          <i class="ri-home-4-line text-lg"></i> На главную
        </a>
      </div>

      <!-- 💬 Футер -->
      <p class="mt-10 text-xs text-gray-400">
        © {{ date('Y') }} WebVitrina — мы строим лучшие решения для продавцов.
      </p>
    </div>
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-app-layout>
