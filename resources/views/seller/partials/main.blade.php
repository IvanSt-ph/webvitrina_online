{{-- resources/views/seller/partials/main.blade.php --}}
<section class="space-y-6 rounded-none border-0 bg-transparent p-0 sm:rounded-2xl sm:border sm:border-neutral-200 sm:bg-white sm:p-6 lg:p-8">

    {{-- 🔹 Заголовок --}}
    @include('seller.partials.header')

    {{-- ✅ Анимированное уведомление об успехе --}}
    @include('seller.partials.success-notification')

    {{-- 🔹 Основная форма в карточках --}}
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PATCH')

        {{-- 🎭 Аватар и имя --}}
        <div class="space-y-5 rounded-2xl bg-neutral-50 p-4 sm:p-6">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <i class="ri-image-line text-brand-500"></i>
                Личные данные
            </h3>
            
            <div class="flex flex-col items-center gap-6 lg:flex-row lg:items-start">
                @include('seller.partials.avatar')
                @include('seller.partials.personal-fields')
            </div>
        </div>

        {{-- 💾 Кнопка сохранения --}}
        @include('seller.partials.submit-button')
    </form>

    {{-- 📱 Телефон магазина --}}
    @if (Auth::user()->shop)
        @include('seller.partials.shop-phone')
    @endif

    {{-- 📧 Верификация email --}}
    @include('seller.partials.email-verification')

</section>

{{-- Подключаем стили и скрипты --}}
@push('styles')
    <style>[x-cloak] { display: none !important; }</style>
@endpush









{{-- 
╔═══════════════════════════════════════════════════════════════════╗
║   СТРУКТУРА ПРОФИЛЯ ПРОДАВЦА                                       ║
╚═══════════════════════════════════════════════════════════════════╝

📁 resources/views/seller/partials/
├── 📄 main.blade.php              # Главный файл (собирает всё)
├── 📄 header.blade.php             # Заголовок + дата регистрации
├── 📄 success-notification.blade.php # Уведомление об успехе
├── 📄 avatar.blade.php             # Аватар + кроппер (JS внутри)
├── 📄 personal-fields.blade.php     # Поля (имя, email)
├── 📄 submit-button.blade.php       # Кнопка сохранения
├── 📄 email-verification.blade.php  # Блок верификации email
├── 📄 shop-phone.blade.php          # Главный блок телефона
│
📁 seller/partials/phone/           # Компоненты телефона магазина
├── 📄 verified.blade.php            # Телефон подтверждён
├── 📄 unverified.blade.php          # Телефон не подтверждён
├── 📄 update-form.blade.php         # Форма изменения номера
├── 📄 verify-form.blade.php         # Форма отправки кода
├── 📄 verification-flow.blade.php   # Процесс верификации
└── 📄 verify-code-form.blade.php    # Форма ввода кода

📁 public/js/profile/                # JavaScript файлы
└── 📄 avatar-cropper.js             # Логика обрезки аватара

─────────────────────────────────────────────────────────────────────
📁 resources/views/profile/partials/  # Стандартные компоненты (Breeze)
├── 📄 delete-user-form.blade.php     # Удаление аккаунта
├── 📄 update-password-form.blade.php # Смена пароля
├── 📄 update-profile-information-form.blade.php # Стандартный профиль
└── 📄 category-menu.blade.php        # Меню категорий (мобилка)
--}}
