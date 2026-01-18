<section class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8 space-y-8">

  {{-- 🔹 Заголовок с градиентом --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center shadow-sm">
        <i class="ri-user-line text-white text-lg"></i>
      </div>
      <div>
        <h2 class="text-xl font-bold text-gray-900">Профиль пользователя</h2>
        <p class="text-sm text-gray-500 mt-0.5">Управление личными данными и настройками</p>
      </div>
    </div>
    <div class="flex items-center gap-2 text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
      <i class="ri-history-line"></i>
      <span>Обновлён: {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}</span>
    </div>
  </div>

  {{-- ✅ Анимированное уведомление об успехе --}}
  @if (session('status') === 'profile-updated')
    <div x-data="{ show: true }" 
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
          <i class="ri-check-line text-green-600"></i>
        </div>
        <div class="flex-1">
          <p class="font-medium text-green-800">Профиль обновлён!</p>
          <p class="text-sm text-green-600">Изменения сохранены успешно</p>
        </div>
        <button @click="show = false" class="text-green-400 hover:text-green-600">
          <i class="ri-close-line text-lg"></i>
        </button>
      </div>
    </div>
  @endif

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
        {{-- Аватар --}}
        <div class="relative group">
          <div class="relative">
            <img src="{{ Auth::user()->avatar_url }}"
                 alt="Аватар"
                 class="w-32 h-32 rounded-2xl border-4 border-white shadow-lg object-cover 
                        transition-all duration-300 group-hover:scale-105 group-hover:shadow-xl">
            <div class="absolute inset-0 rounded-2xl bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
          </div>
          
          <label class="absolute -bottom-2 -right-2 bg-white border border-gray-200 shadow-lg 
                        rounded-full w-10 h-10 flex items-center justify-center cursor-pointer 
                        hover:bg-indigo-50 hover:border-indigo-200 transition-all duration-200 
                        group-hover:scale-110">
            <i class="ri-camera-line text-gray-600 group-hover:text-indigo-600"></i>
            <input type="file" name="avatar" class="hidden" accept="image/*">
          </label>
          
          <div class="text-center mt-4">
            <button type="button" onclick="document.querySelector('input[name=avatar]').click()" 
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
              Сменить фото
            </button>
          </div>
        </div>

        {{-- Имя --}}
        <div class="flex-1 w-full space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
              <i class="ri-user-3-line text-gray-400"></i>
              Имя пользователя
            </label>
            <div class="relative">
              <input type="text" 
                     name="name" 
                     value="{{ old('name', Auth::user()->name) }}"
                     maxlength="255"
                     class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 
                            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 
                            transition-all duration-200 @error('name') border-red-500 @enderror"
                     placeholder="Введите ваше имя">
              <i class="ri-user-3-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            @error('name')
              <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                <i class="ri-error-warning-line"></i> {{ $message }}
              </p>
            @enderror
          </div>

          {{-- Email с статусом --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
              <i class="ri-mail-line text-gray-400"></i>
              Электронная почта
            </label>
            <div class="relative">
              <input type="email" 
                     name="email"
                     value="{{ old('email', Auth::user()->email) }}"
                     maxlength="255"
                     class="w-full pl-10 pr-10 py-3 rounded-xl border border-gray-300 
                            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 
                            transition-all duration-200 @error('email') border-red-500 @enderror"
                     placeholder="email@example.com">
              <i class="ri-mail-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              @if (Auth::user()->hasVerifiedEmail())
                <i class="ri-checkbox-circle-fill absolute right-3 top-1/2 -translate-y-1/2 text-green-500" 
                   title="Email подтверждён"></i>
              @endif
            </div>
            @error('email')
              <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                <i class="ri-error-warning-line"></i> {{ $message }}
              </p>
            @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- 📊 Статистика профиля --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-100">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
            <i class="ri-calendar-line text-blue-600"></i>
          </div>
          <div>
            <p class="text-sm text-gray-600">Дата регистрации</p>
            <p class="text-lg font-semibold text-gray-900">
              {{ Auth::user()->created_at?->format('d.m.Y') ?? '—' }}
            </p>
          </div>
        </div>
      </div>

      @if (Auth::user()->shop)
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-5 border border-purple-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
              <i class="ri-store-2-line text-purple-600"></i>
            </div>
            <div class="flex-1">
              <p class="text-sm text-gray-600">Ваш магазин</p>
              <p class="text-lg font-semibold text-gray-900 truncate">{{ Auth::user()->shop->name ?? 'Без названия' }}</p>
              <p class="text-xs text-gray-500 mt-1">
                Создан {{ Auth::user()->shop->created_at?->diffForHumans() }}
              </p>
            </div>
            <a href="{{ route('seller.show', Auth::user()->id) }}" 
               class="inline-flex items-center gap-1 text-purple-600 hover:text-purple-800 group">
              <span class="text-sm font-medium group-hover:underline">В магазин</span>
              <i class="ri-arrow-right-s-line text-lg transition-transform group-hover:translate-x-1"></i>
            </a>
          </div>
        </div>
      @endif
    </div>

    {{-- 💾 Кнопка сохранения --}}
    <div class="flex justify-end">
      <button type="submit"
              class="relative overflow-hidden group px-6 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 
                     hover:from-indigo-700 hover:to-purple-700 text-white font-medium rounded-xl 
                     shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5
                     flex items-center gap-2">
        <span class="relative z-10 flex items-center gap-2">
          <i class="ri-save-3-line text-lg"></i>
          Сохранить изменения
        </span>
        <span class="absolute inset-0 bg-gradient-to-r from-indigo-700 to-purple-700 translate-y-full 
                     group-hover:translate-y-0 transition-transform duration-300"></span>
      </button>
    </div>
  </form>





{{-- 📱 Телефон магазина --}}
@if (Auth::user()->shop)
    <div class="border-t border-gray-100 pt-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <i class="ri-phone-line text-gray-400"></i>
                    Телефон магазина
                </h3>
                <p class="text-sm text-gray-500 mt-1">Номер для связи с покупателями</p>
            </div>
            @if(Auth::user()->shop->is_phone_verified)
                <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full 
                           flex items-center gap-1.5">
                    <i class="ri-checkbox-circle-fill"></i> Подтверждён
                </span>
            @elseif(Auth::user()->shop->phone)
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-full 
                           flex items-center gap-1.5">
                    <i class="ri-shield-line"></i> Не подтверждён
                </span>
            @endif
        </div>

        {{-- Статус верификации --}}
        @if(Auth::user()->shop->is_phone_verified)
            <div x-data="{ editing: false, newPhone: '{{ Auth::user()->shop->phone ?? '' }}' }">
                {{-- Режим просмотра --}}
                <div x-show="!editing" 
                     class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-5 border border-green-200">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="ri-phone-fill text-green-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">
                                @if(Auth::user()->shop->phone)
                                    {{ Auth::user()->shop->phone }}
                                @else
                                    Телефон не указан
                                @endif
                            </p>
                            <p class="text-sm text-gray-600 mt-1">Телефон успешно верифицирован</p>
                        </div>
                        <button @click="editing = true" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 
                                       rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                            <i class="ri-pencil-line"></i>
                            Изменить
                        </button>
                    </div>
                </div>

                {{-- Режим редактирования --}}
                <div x-show="editing" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-200 mt-4">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-gray-900">Изменить номер телефона</h4>
                            <button @click="editing = false; newPhone = '{{ Auth::user()->shop->phone ?? '' }}'" 
                                    class="text-gray-400 hover:text-gray-600">
                                <i class="ri-close-line text-lg"></i>
                            </button>
                        </div>
                        
                        {{-- Форма сохранения номера (без верификации) --}}
                        <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-4" id="update-phone-form">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="update_type" value="phone">
                            
                            <div class="space-y-3">
                                <label class="block text-sm font-medium text-gray-700">Новый номер телефона</label>
                                <div class="relative">
                                    <input id="update-phone-input" 
                                           type="tel" 
                                           name="phone"
                                           x-model="newPhone"
                                           placeholder="+373 777 00 000"
                                           class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-300 
                                                  focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 
                                                  transition-all duration-200"
                                           required>
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                                        <i class="ri-phone-line text-gray-400"></i>
                                        <span class="text-gray-300">|</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500">Номер будет сохранён, но потребуется повторная верификация</p>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <button type="submit"
                                        class="px-5 py-3 bg-gradient-to-r from-indigo-500 to-blue-500 
                                               hover:from-indigo-600 hover:to-blue-600 text-white font-medium 
                                               rounded-xl shadow-sm hover:shadow-md transition-all duration-200 
                                               flex items-center gap-2">
                                    <i class="ri-save-3-line"></i>
                                    Сохранить номер
                                </button>
                                <button type="button" 
                                        @click="editing = false; newPhone = '{{ Auth::user()->shop->phone ?? '' }}'"
                                        class="px-5 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 
                                               rounded-xl hover:bg-gray-50 transition-colors">
                                    Отмена
                                </button>
                            </div>
                        </form>
                        
                        {{-- Разделитель --}}
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-blue-50 text-gray-500">или</span>
                            </div>
                        </div>
                        
                        {{-- Форма отправки кода для верификации --}}
                        <form method="POST" action="{{ route('shop.phone.send') }}" class="space-y-4" id="verify-phone-form">
                            @csrf
                            <div class="space-y-3">
                                <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                    <i class="ri-shield-check-line text-green-600"></i>
                                    Верифицировать текущий номер
                                </label>
                                <div class="bg-amber-50 border border-amber-100 rounded-lg p-4 text-sm text-amber-800">
                                    <div class="flex items-start gap-2">
                                        <i class="ri-information-line text-amber-600 mt-0.5"></i>
                                        <div>
                                            <p class="font-medium">После изменения номера потребуется верификация</p>
                                            <p class="text-xs mt-1">На текущий номер будет отправлен SMS-код для подтверждения</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <button type="submit"
                                        class="px-5 py-3 bg-gradient-to-r from-amber-500 to-orange-500 
                                               hover:from-amber-600 hover:to-orange-600 text-white font-medium 
                                               rounded-xl shadow-sm hover:shadow-md transition-all duration-200 
                                               flex items-center gap-2">
                                    <i class="ri-send-plane-line"></i>
                                    Отправить код подтверждения
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            {{-- Если телефон не подтвержден --}}
            <div class="space-y-6">
                {{-- Форма сохранения/изменения номера --}}
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-200">
                    <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                        <i class="ri-phone-line text-blue-600"></i>
                        Настройка номера телефона
                    </h4>
                    
                    <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-4" id="shop-phone-save-form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="update_type" value="phone">
                        
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">Номер телефона магазина</label>
                            <div class="relative">
                                <input id="shop-phone-input" 
                                       type="tel" 
                                       name="phone"
                                       value="{{ old('phone', Auth::user()->shop?->phone) }}"
                                       placeholder="+373 777 00 000"
                                       class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-300 
                                              focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 
                                              transition-all duration-200"
                                       required>
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                                    <i class="ri-phone-line text-gray-400"></i>
                                    <span class="text-gray-300">|</span>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('phone')" class="mt-1 text-sm" />
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <button type="submit"
                                    class="px-5 py-3 bg-gradient-to-r from-indigo-500 to-blue-500 
                                           hover:from-indigo-600 hover:to-blue-600 text-white font-medium 
                                           rounded-xl shadow-sm hover:shadow-md transition-all duration-200 
                                           flex items-center gap-2">
                                <i class="ri-save-3-line"></i>
                                Сохранить номер
                            </button>
                            <p class="text-sm text-gray-500">После сохранения можно будет верифицировать</p>
                        </div>
                    </form>
                </div>

                {{-- Форма верификации (только если номер уже сохранен) --}}
                @if(Auth::user()->shop->phone)
                    <div class="space-y-6">
                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-5 border border-amber-200">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ri-shield-keyhole-line text-amber-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Подтвердите телефон магазина</p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Это повысит доверие покупателей и откроет дополнительные возможности
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Форма отправки кода --}}
                        <form method="POST" 
                              action="{{ route('shop.phone.send') }}" 
                              class="bg-gray-50 rounded-xl p-5 space-y-4"
                              id="shop-phone-verify-form">
                            @csrf
                            <div class="space-y-3">
                                <label class="block text-sm font-medium text-gray-700">
                                    Отправить код подтверждения на номер:
                                    <span class="font-semibold text-gray-900">{{ Auth::user()->shop->phone }}</span>
                                </label>
                                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-sm text-blue-800">
                                    <div class="flex items-start gap-2">
                                        <i class="ri-information-line text-blue-600 mt-0.5"></i>
                                        <p>На указанный номер будет отправлен SMS с 6-значным кодом подтверждения</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <button type="submit"
                                        class="px-5 py-3 bg-gradient-to-r from-amber-500 to-orange-500 
                                               hover:from-amber-600 hover:to-orange-600 text-white font-medium 
                                               rounded-xl shadow-sm hover:shadow-md transition-all duration-200 
                                               flex items-center gap-2">
                                    <i class="ri-send-plane-line"></i>
                                    Отправить код
                                </button>
                                <p class="text-sm text-gray-500">Код придёт в течение минуты</p>
                            </div>
                        </form>

                        {{-- Форма ввода кода --}}
                        @if(session('shop_phone_verification_sent'))
                            <form method="POST" 
                                  action="{{ route('shop.phone.verify') }}" 
                                  class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-5 border border-green-200"
                                  x-data="{ timer: 600, formattedTime: '10:00' }"
                                  x-init="
                                    let interval = setInterval(() => {
                                      timer--;
                                      let minutes = Math.floor(timer / 60);
                                      let seconds = timer % 60;
                                      formattedTime = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                                      if(timer <= 0) clearInterval(interval);
                                    }, 1000);
                                  ">
                                @csrf
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <p class="font-medium text-gray-900">Введите 6-значный код</p>
                                        <div class="flex items-center gap-1 text-sm text-green-600 font-medium">
                                            <i class="ri-time-line"></i>
                                            <span x-text="formattedTime"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1">
                                            <input type="text" 
                                                   name="code" 
                                                   placeholder="000000"
                                                   maxlength="6"
                                                   autocomplete="off"
                                                   class="w-full text-center text-2xl font-bold tracking-widest 
                                                          py-3 rounded-xl border border-gray-300 
                                                          focus:border-green-500 focus:ring-2 focus:ring-green-100"
                                                   required>
                                        </div>
                                        <button type="submit"
                                                class="px-5 py-3 bg-gradient-to-r from-green-500 to-emerald-500 
                                                       hover:from-green-600 hover:to-emerald-600 text-white font-medium 
                                                       rounded-xl shadow-sm hover:shadow-md transition-all duration-200 
                                                       flex items-center gap-2">
                                            <i class="ri-check-line"></i>
                                            Подтвердить
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 text-center">Код отправлен на указанный номер</p>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- 📞 Скрипт для intl-tel-input --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализация всех полей ввода телефона
        const phoneInputs = [
            { id: 'shop-phone-input', form: 'shop-phone-save-form' },
            { id: 'update-phone-input', form: 'update-phone-form' },
            { id: 'change-phone-input', form: 'change-phone-form' }
        ];
        
        phoneInputs.forEach(config => {
            const input = document.querySelector(`#${config.id}`);
            if(input){
                const iti = window.intlTelInput(input, {
                    initialCountry: "md",
                    preferredCountries: ["md", "ru", "ro", "ua"],
                    separateDialCode: true,
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js",
                });
                
                const form = document.querySelector(`#${config.form}`);
                if(form){
                    form.addEventListener('submit', function(e){
                        input.value = iti.getNumber();
                    });
                }
            }
        });
        
        // Форма верификации
        const verifyForm = document.querySelector('#shop-phone-verify-form');
        if(verifyForm){
            verifyForm.addEventListener('submit', function(e){
                // Для формы верификации используем сохраненный номер
                const phone = "{{ Auth::user()->shop->phone }}";
                if(phone){
                    // Можно добавить скрытое поле с номером, если нужно
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'phone';
                    hiddenInput.value = phone;
                    verifyForm.appendChild(hiddenInput);
                }
            });
        }
    });
    </script>
@endif

  {{-- 📧 Верификация email --}}
  @if (!Auth::user()->hasVerifiedEmail())
    <div class="border-t border-gray-100 pt-8">
      <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-5 border border-orange-200">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
            <i class="ri-mail-warning-line text-orange-600 text-xl"></i>
          </div>
          <div class="flex-1">
            <h4 class="font-semibold text-gray-900">Подтвердите email</h4>
            <p class="text-sm text-gray-600 mt-1 mb-3">
              Для полного доступа к функциям платформы необходимо подтвердить email
            </p>
            <form method="POST" action="{{ route('verification.send') }}">
              @csrf
              <button type="submit"
                      class="px-5 py-2.5 bg-gradient-to-r from-orange-500 to-red-500 
                             hover:from-orange-600 hover:to-red-600 text-white font-medium 
                             rounded-lg shadow-sm hover:shadow-md transition-all duration-200 
                             flex items-center gap-2">
                <i class="ri-mail-send-line"></i>
                Отправить письмо подтверждения
              </button>
            </form>
          </div>
        </div>
        
        @if (session('status') === 'verification-link-sent')
          <div class="mt-4 p-3 bg-green-100 text-green-800 text-sm rounded-lg flex items-center gap-2">
            <i class="ri-check-double-line"></i>
            Письмо отправлено! Проверьте вашу почту.
          </div>
        @endif
      </div>
    </div>
  @endif

</section>

{{-- 🔹 Подключение стилей --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>

{{-- 🔹 Alpine.js для анимаций --}}
<script src="//unpkg.com/alpinejs" defer></script>