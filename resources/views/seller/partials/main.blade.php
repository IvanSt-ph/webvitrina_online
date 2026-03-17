{{-- resources/views/seller/partials/main.blade.php --}}
<section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8 space-y-8">

    {{-- 🔹 Заголовок с градиентом --}}
    @include('seller.partials.header')

    {{-- ✅ Анимированное уведомление об успехе --}}
    @include('seller.partials.success-notification')

    {{-- 🔹 Основная форма в карточках --}}
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PATCH')

        {{-- 🎭 Аватар и имя --}}
        <div class="bg-gray-50 rounded-xl p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-image-line text-indigo-500"></i>
                Личные данные
            </h3>
            
            <div class="flex flex-col lg:flex-row items-center gap-8">
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <style>[x-cloak] { display: none !important; }</style>
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="{{ asset('js/profile/avatar-cropper.js') }}"></script>
    <script src="{{ asset('js/profile/phone-input.js') }}"></script>
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
├── 📄 avatar-cropper.js             # Логика обрезки аватара
└── 📄 phone-input.js                 # Инициализация полей телефона

─────────────────────────────────────────────────────────────────────
📁 resources/views/profile/partials/  # Стандартные компоненты (Breeze)
├── 📄 delete-user-form.blade.php     # Удаление аккаунта
├── 📄 update-password-form.blade.php # Смена пароля
├── 📄 update-profile-information-form.blade.php # Стандартный профиль
└── 📄 category-menu.blade.php        # Меню категорий (мобилка)
--}}