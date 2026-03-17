{{-- resources/views/profile/index.blade.php --}}
<section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8 space-y-8">

    {{-- 🔹 Заголовок с градиентом --}}
    @include('profile.partials.header')

    {{-- ✅ Анимированное уведомление об успехе --}}
    @include('profile.partials.success-notification')

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
                @include('profile.partials.avatar')
                @include('profile.partials.personal-fields')
            </div>
        </div>

        {{-- 💾 Кнопка сохранения --}}
        @include('profile.partials.submit-button')
    </form>

    {{-- 📱 Телефон магазина --}}
    @if (Auth::user()->shop)
        @include('profile.partials.shop-phone')
    @endif

    {{-- 📧 Верификация email --}}
    @include('profile.partials.email-verification')

    

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