{{-- resources/views/profile/edit.blade.php --}}
<x-seller-layout title="Профиль продавца">

  <div class="min-h-screen bg-white text-gray-800">
    <main x-data="{ tab: 'main', mobileOpen: null }"
          class="pt-2 pb-10 space-y-10 px-4 sm:px-6 lg:px-8 max-w-none   mx-auto">

      {{-- 🏪 Баннер магазина --}}
      <section id="banner-box"
               class="relative w-full rounded-2xl overflow-hidden mb-8
                      border border-indigo-100 shadow-md bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50">
        <div class="relative w-full" style="padding-top:21%;">
          <img id="banner-preview"
               src="{{ Auth::user()->shop?->banner_url }}"
               alt="Баннер магазина"
               class="absolute inset-0 w-full h-full object-cover transition-all duration-700 ease-in-out">
          <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent"></div>

          <div class="absolute bottom-3 left-4 sm:left-6 text-white drop-shadow-lg">
            <h2 class="text-xl sm:text-2xl font-semibold tracking-wide">
              {{ Auth::user()->shop?->name ?? 'Ваш магазин' }}
            </h2>
            <p class="text-sm opacity-90">{{ Auth::user()->shop?->city ?? 'Город не указан' }}</p>
          </div>

          <div class="absolute top-3 right-3 flex gap-2 z-10">
            <label class="bg-white/80 hover:bg-white px-3 py-2 rounded-lg text-sm text-gray-700 cursor-pointer shadow-sm border border-gray-200 transition-all flex items-center gap-1 backdrop-blur-sm">
              <i class="ri-image-add-line text-indigo-500"></i> Изменить
              <input type="file" id="banner-input" class="hidden" accept="image/*">
            </label>

            @if (Auth::user()->shop?->banner)
              <form method="POST" action="{{ route('profile.shop.update') }}" onsubmit="return confirm('Удалить баннер магазина?')">
                @csrf
                @method('PATCH')
                <input type="hidden" name="remove_banner" value="1">
                <button type="submit"
                        class="bg-white/80 hover:bg-white px-3 py-2 rounded-lg text-sm text-red-600 font-medium shadow-sm border border-gray-200 transition-all flex items-center gap-1 backdrop-blur-sm">
                  <i class="ri-delete-bin-line"></i> Удалить
                </button>
              </form>
            @endif
          </div>
        </div>

        {{-- ✂️ Модалка обрезки --}}
        <div id="cropper-modal"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 transition-all">
          <div class="bg-white rounded-2xl shadow-2xl p-5 sm:p-6 w-[95%] sm:w-[600px] max-w-[90vw] animate-fade-in">
            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
              <i class="ri-crop-line text-indigo-500"></i> Обрезка баннера
            </h3>

            <div class="max-h-[60vh] overflow-hidden rounded-lg border border-gray-200 flex justify-center bg-gray-50">
              <img id="cropper-image" class="max-w-full select-none">
            </div>

            <div class="flex justify-end gap-3 mt-4 pt-3 border-t border-gray-100">
              <button id="cancel-crop"
                      class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition">
                Отмена
              </button>
              <button id="save-crop"
                      class="px-5 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 text-white rounded-lg font-medium shadow-sm transition">
                Сохранить
              </button>
            </div>
          </div>
        </div>
      </section>

      {{-- 🔝 Заголовок --}}
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 flex items-center gap-2">
            <i class="ri-user-settings-line text-indigo-600"></i>
            Профиль продавца
          </h1>
          <p class="text-sm text-gray-500 mt-1">Редактируйте данные компании, контакты и безопасность аккаунта</p>
        </div>
      </div>

      {{-- ✅ Уведомления --}}
      @if (session('status'))
        @php
          $messages = [
            'profile-updated' => ['bg-blue-50 border-blue-200 text-blue-700', 'ri-store-2-line', 'Личные данные обновлены'],
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

      {{-- 🧭 Вкладки (десктоп) --}}
      <div class="hidden md:flex border-b border-gray-200 overflow-x-auto">
        <button @click="tab = 'main'" :class="tab === 'main' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600'"
                class="px-4 py-2 text-sm font-medium whitespace-nowrap">
          Основная информация
        </button>
        <button @click="tab = 'shop'" :class="tab === 'shop' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600'"
                class="px-4 py-2 text-sm font-medium whitespace-nowrap">
          Информация о магазине
        </button>
        <button @click="tab = 'security'" :class="tab === 'security' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600'"
                class="px-4 py-2 text-sm font-medium whitespace-nowrap">
          Безопасность
        </button>
      </div>

      {{-- 📱 Гармошки (мобильная версия) --}}
      <div class="block md:hidden space-y-4">
        <template x-for="section in ['main', 'shop', 'security']" :key="section">
          <div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <header @click="mobileOpen = mobileOpen === section ? null : section"
                    class="flex items-center justify-between px-4 py-3 bg-gray-50 cursor-pointer">
              <span class="text-sm font-medium text-gray-800" x-text="
                section === 'main' ? 'Основная информация' :
                section === 'shop' ? 'Информация о магазине' :
                'Безопасность аккаунта'"></span>
              <i class="ri-arrow-down-s-line text-lg text-gray-500 transition"
                 :class="mobileOpen === section ? 'rotate-180' : ''"></i>
            </header>
            <div x-show="mobileOpen === section" class="bg-white">
              <div class="p-4">
                <template x-if="section === 'main'">
                  @include('seller.partials.main')
                </template>
                <template x-if="section === 'shop'">
                  @include('seller.partials.shop')
                </template>
                <template x-if="section === 'security'">
                  @include('seller.partials.security')
                </template>
              </div>
            </div>
          </div>
        </template>
      </div>

      {{-- 💻 Контент вкладок (десктоп) --}}
      <div class="hidden md:block">
        <div x-show="tab === 'main'" x-transition>
          @include('seller.partials.main')
        </div>
        <div x-show="tab === 'shop'" x-transition>
          @include('seller.partials.shop')
        </div>
        <div x-show="tab === 'security'" x-transition>
          @include('seller.partials.security')
        </div>
      </div>

    </main>
  </div>

  {{-- ✅ Cropper.js --}}
  <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

  @include('layouts.mobile-bottom-seller-nav')

 <script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('banner-input');
  const modal = document.getElementById('cropper-modal');
  const img = document.getElementById('cropper-image');
  const cancelBtn = document.getElementById('cancel-crop');
  const saveBtn = document.getElementById('save-crop');
  let cropper;

  // 🖼️ Загрузка файла
  input.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      img.src = reader.result;
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      document.body.classList.add('overflow-hidden');
      cropper && cropper.destroy();
      cropper = new Cropper(img, {
        aspectRatio: 16 / 3.36,
        viewMode: 2,
        dragMode: 'move',
        background: false,
        autoCropArea: 1,
      });
    };
    reader.readAsDataURL(file);
  });

  // ❌ Отмена
  cancelBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    cropper?.destroy();
    input.value = '';
  });

  // 💾 Сохранение
  saveBtn.addEventListener('click', () => {
    saveBtn.textContent = 'Сохраняем...';
    saveBtn.disabled = true;
    const canvas = cropper.getCroppedCanvas({
      width: 1600,
      height: 1600 / (16 / 3.36),
    });
    canvas.toBlob(blob => {
      const formData = new FormData();
      formData.append('_token', '{{ csrf_token() }}');
      formData.append('_method', 'PATCH'); // 🔹 именно PATCH!
      formData.append('banner', blob, 'banner.jpg');

      fetch('{{ route('profile.shop.update') }}', {
        method: 'POST', // Laravel примет как PATCH, т.к. есть _method
        body: formData
      })
        .then(response => {
          if (!response.ok) throw new Error('Ошибка сохранения');
          saveBtn.textContent = 'Сохранено!';
          setTimeout(() => location.reload(), 700);
        })
        .catch(() => {
          saveBtn.textContent = 'Ошибка!';
          saveBtn.disabled = false;
        });
    }, 'image/jpeg', 0.9);
  });
});
</script>


  <style>
    @keyframes fade-in { from { opacity: 0; transform: scale(0.97); } to { opacity: 1; transform: scale(1); } }
    .animate-fade-in { animation: fade-in 0.25s ease-out; }
    [x-cloak] { display: none !important; }
  </style>

</x-seller-layout>
