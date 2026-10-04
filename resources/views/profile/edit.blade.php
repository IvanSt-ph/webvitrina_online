{{-- resources/views/profile/edit.blade.php --}}
<x-seller-layout title="Профиль продавца">

  <div class="min-h-screen w-full bg-white text-neutral-900">
    <main x-data="{ tab: 'main', mobileOpen: null }"
          class="w-full space-y-5 px-3 py-4 pb-28 sm:space-y-6 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">

      {{-- 🏪 Баннер магазина --}}
      <section id="banner-box"
               class="relative w-full overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-100">
        <div class="relative min-h-44 w-full sm:min-h-52 lg:min-h-64">
          <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}" id="banner-preview"
               src="{{ Auth::user()->shop?->banner_url ?? asset('images/image-placeholder.svg') }}"
               alt="Баннер магазина"
               class="absolute inset-0 h-full w-full object-cover transition-all duration-700 ease-in-out">
          <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-transparent"></div>

          <div class="absolute bottom-4 left-4 text-white sm:bottom-5 sm:left-5">
            <h2 class="text-xl font-semibold tracking-tight sm:text-2xl">
              {{ Auth::user()->shop?->name ?? 'Ваш магазин' }}
            </h2>
            <p class="text-sm opacity-90">{{ Auth::user()->shop?->city ?? 'Город не указан' }}</p>
          </div>

          <div class="absolute right-3 top-3 z-10 flex flex-wrap justify-end gap-2">
            <label class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-white/60 bg-white/90 px-3 py-2 text-sm font-semibold text-neutral-700 backdrop-blur-sm transition hover:bg-white">
              <i class="ri-image-add-line text-brand-500"></i> Изменить
              <input type="file" id="banner-input" class="hidden" accept="image/*">
            </label>

            @if (Auth::user()->shop?->banner)
              <form method="POST" action="{{ route('profile.shop.update') }}" onsubmit="return confirm('Удалить баннер магазина?')">
                @csrf
                @method('PATCH')
                <input type="hidden" name="remove_banner" value="1">
                <button type="submit"
                        class="flex items-center gap-1.5 rounded-xl border border-white/60 bg-white/90 px-3 py-2 text-sm font-semibold text-rose-600 backdrop-blur-sm transition hover:bg-white">
                  <i class="ri-delete-bin-line"></i> Удалить
                </button>
              </form>
            @endif
          </div>
        </div>

        {{-- ✂️ Модалка обрезки --}}
        <div id="cropper-modal"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 transition-all">
          <div class="animate-fade-in w-[95%] max-w-[90vw] rounded-2xl bg-white p-5 shadow-2xl sm:w-[600px] sm:p-6">
            <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold text-neutral-900">
              <i class="ri-crop-line text-brand-500"></i> Обрезка баннера
            </h3>

            <div class="flex max-h-[60vh] justify-center overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50">
              <img id="cropper-image" class="max-w-full select-none">
            </div>

            <div class="mt-4 flex justify-end gap-3 border-t border-neutral-100 pt-3">
              <button id="cancel-crop"
                      class="rounded-xl px-4 py-2 font-semibold text-neutral-700 transition hover:bg-neutral-100">
                Отмена
              </button>
              <button id="save-crop"
                      class="rounded-xl bg-brand-500 px-5 py-2 font-semibold text-white transition hover:bg-brand-600">
                Сохранить
              </button>
            </div>
          </div>
        </div>
      </section>

      {{-- 🔝 Заголовок --}}
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600"><i class="ri-user-settings-line"></i>Настройки</div>
          <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Профиль продавца</h1>
          <p class="mt-1 text-sm leading-6 text-neutral-500">Редактируйте данные компании, контакты и безопасность аккаунта</p>
        </div>
      </div>

      {{-- ✅ Уведомления --}}
      @if (session('status'))
        @php
          $messages = [
            'profile-updated' => ['bg-brand-50 border-brand-200 text-brand-700', 'ri-store-2-line', 'Личные данные обновлены'],
            'shop-updated'    => ['bg-brand-50 border-brand-200 text-brand-700', 'ri-store-2-line', 'Информация о магазине обновлена'],
          ];
          [$classes, $icon, $text] = $messages[session('status')] ?? ['bg-neutral-50 border-neutral-200 text-neutral-700', 'ri-information-line', 'Изменения сохранены'];
        @endphp
        <div class="flex items-center gap-2 rounded-2xl border p-4 text-sm {{ $classes }}">
          <i class="{{ $icon }} text-lg"></i>
          <span>{{ $text }}</span>
        </div>
      @endif

      @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
          <strong class="block mb-1">Ошибка при сохранении:</strong>
          <ul class="list-disc ml-5 space-y-0.5">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- 🧭 Вкладки (десктоп) --}}
      <div class="hidden overflow-x-auto rounded-2xl border border-neutral-200 bg-neutral-50 p-1 md:flex">
        <button @click="tab = 'main'" :aria-pressed="tab === 'main'" type="button"
                class="wv-ui-tab min-h-11 whitespace-nowrap rounded-xl px-4 py-2 text-sm">
          Основная информация
        </button>
        <button @click="tab = 'shop'" :aria-pressed="tab === 'shop'" type="button"
                class="wv-ui-tab min-h-11 whitespace-nowrap rounded-xl px-4 py-2 text-sm">
          Информация о магазине
        </button>
        <button @click="tab = 'security'" :aria-pressed="tab === 'security'" type="button"
                class="wv-ui-tab min-h-11 whitespace-nowrap rounded-xl px-4 py-2 text-sm">
          Безопасность
        </button>
      </div>

      {{-- 📱 Гармошки (мобильная версия) --}}
      <div class="block md:hidden space-y-3">
        <template x-for="section in ['main', 'shop', 'security']" :key="section">
          <div class="overflow-hidden rounded-2xl border border-neutral-200">
            <button type="button" @click="mobileOpen = mobileOpen === section ? null : section"
                    :aria-expanded="mobileOpen === section" :aria-controls="'profile-mobile-' + section"
                    class="wv-ui-menu-link flex min-h-12 w-full items-center justify-between bg-neutral-50 px-4 py-3 text-left">
              <span class="text-sm" x-text="
                section === 'main' ? 'Основная информация' :
                section === 'shop' ? 'Информация о магазине' :
                'Безопасность аккаунта'"></span>
              <i class="ri-arrow-down-s-line text-lg transition-transform duration-150" aria-hidden="true"
                 :class="mobileOpen === section ? 'rotate-180' : ''"></i>
            </button>
            <div :id="'profile-mobile-' + section" x-show="mobileOpen === section" class="bg-white">
              <div class="p-3 sm:p-4">
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
  @once('remixicon-4.1.0')
      @vite('resources/css/remixicon.css')
  @endonce

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
