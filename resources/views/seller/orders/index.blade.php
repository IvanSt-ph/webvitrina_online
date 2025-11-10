<x-seller-layout title="Заказы продавца">

  <div class="min-h-[70vh] flex flex-col items-center justify-center text-center px-6 py-16 relative overflow-hidden">

      {{-- 💫 Фоновые ореолы --}}
      <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-purple-50 opacity-90"></div>
      <div class="absolute top-1/3 left-1/2 -translate-x-1/2 w-[400px] h-[400px] bg-indigo-300/20 rounded-full blur-3xl animate-pulse-slow"></div>

      {{-- 📦 Иконка --}}
      <div class="relative z-10 mb-6 animate-float">
          <div class="bg-white border border-gray-100 shadow-lg rounded-2xl p-8">
              <i class="ri-file-list-3-line text-6xl text-indigo-500 animate-pulse"></i>
          </div>
      </div>

      {{-- 📝 Заголовок --}}
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-3 z-10">
          Раздел «Заказы» в разработке
      </h1>

      {{-- 💬 Описание --}}
      <p class="text-gray-500 max-w-md mx-auto leading-relaxed mb-8 z-10">
          Скоро здесь появится управление заказами, статусами и сообщениями клиентов.
          Вы сможете отслеживать каждый заказ и взаимодействовать с покупателями.
      </p>

      {{-- 🔄 Кнопка возврата --}}
      <a href="{{ route('cabinet') }}"
         class="relative z-10 inline-flex items-center gap-2 px-5 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-md hover:shadow-lg transition">
          <i class="ri-arrow-left-line text-lg"></i>
          Вернуться в панель продавца
      </a>

  </div>

  @include('layouts.mobile-bottom-seller-nav')

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
  <style>
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-6px); }
    }
    @keyframes pulse-slow {
      0%, 100% { opacity: 0.5; transform: scale(1); }
      50% { opacity: 0.8; transform: scale(1.05); }
    }
    .animate-float { animation: float 3.5s ease-in-out infinite; }
    .animate-pulse-slow { animation: pulse-slow 5s ease-in-out infinite; }
  </style>

</x-seller-layout>
