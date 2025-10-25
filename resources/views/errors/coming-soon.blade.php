<x-app-layout title="Раздел в разработке" :hideHeader="true">
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-white via-gray-50 to-white text-gray-700">

    <div class="max-w-lg w-full text-center px- py-0 animate-fade-in">

      <!-- 🧩 Иллюстрация -->
      <div class="relative mb-10">
        <div class="absolute inset-0 blur-3xl bg-indigo-100/50 rounded-full -z-10 scale-125"></div>
        <img src="{{ asset('images/soon.png') }}"
             alt="В разработке"
             class="w-106 h-106 mx-auto drop-shadow-md opacity-95 transition-transform duration-500 hover:scale-105">
      </div>

      <!-- 🏗 Заголовок -->
      <h1 class="text-4xl font-bold text-gray-900 tracking-tight">
        Раздел в разработке
      </h1>

      <!-- 📜 Подзаголовок -->
      <p class="mt-3 text-base text-gray-500 leading-relaxed">
        Мы уже работаем над этим функционалом.<br>
        Совсем скоро вы сможете воспользоваться всеми возможностями этого раздела.
      </p>

      <!-- 🔘 Кнопки -->
      <div class="mt-10 flex flex-col sm:flex-row justify-center gap-3">
        <a href="{{ url()->previous() }}"
           class="inline-flex justify-center items-center gap-2 px-5 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-all duration-200">
          <i class="ri-arrow-left-line text-lg"></i>
          Назад
        </a>

        <a href="{{ route('home') }}"
           class="inline-flex justify-center items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition-all duration-200">
          <i class="ri-home-4-line text-lg"></i>
          На главную
        </a>
      </div>

      <!-- 💬 Футер -->
      <p class="mt-10 text-xs text-gray-400">
       WebVitrina — мы создаём лучшие решения для продавцов.
      </p>
    </div>
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

  <style>
    /* 🪶 Плавное появление блока */
    @keyframes fade-in {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
      animation: fade-in 0.6s ease-out both;
    }
  </style>
</x-app-layout>
