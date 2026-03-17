{{-- resources/views/profile/partials/phone/verified.blade.php --}}
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
            
            {{-- Форма сохранения номера --}}
            @include('profile.partials.phone.update-form')
            
            {{-- Разделитель --}}
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-blue-50 text-gray-500">или</span>
                </div>
            </div>
            
            {{-- Форма отправки кода --}}
            @include('profile.partials.phone.verify-form')
        </div>
    </div>
</div>