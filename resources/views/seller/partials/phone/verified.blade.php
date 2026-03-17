{{-- resources/views/seller/partials/phone/verified.blade.php --}}
<div x-data="{ editing: false, newPhone: '{{ Auth::user()->shop->phone ?? '' }}' }">
    {{-- Режим просмотра --}}
    <div x-show="!editing" 
         class="bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/80 rounded-xl p-5 border border-indigo-100/50 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-200/50">
                <i class="ri-phone-line text-white text-xl"></i>
            </div>
            <div class="flex-1">
                <p class="font-medium text-gray-900">
                    @if(Auth::user()->shop->phone)
                        {{ Auth::user()->shop->phone }}
                    @else
                        Телефон не указан
                    @endif
                </p>
                <p class="text-sm text-indigo-600/70 mt-1 flex items-center gap-1">
                    <i class="ri-checkbox-circle-fill text-emerald-500"></i>
                    Телефон успешно верифицирован
                </p>
            </div>
            <button @click="editing = true" 
                    class="relative overflow-hidden group px-4 py-2 bg-indigo-500/80 hover:bg-indigo-600 
                           text-white font-medium rounded-lg shadow-md hover:shadow-lg 
                           transition-all duration-300 transform hover:-translate-y-0.5
                           flex items-center gap-2 backdrop-blur-sm border border-indigo-400/30 text-sm">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="ri-pencil-line text-lg"></i>
                    Изменить
                    <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
                </span>
                <span class="absolute inset-0 bg-indigo-600 translate-y-full 
                             group-hover:translate-y-0 transition-transform duration-300"></span>
            </button>
        </div>
    </div>

    {{-- Режим редактирования --}}
    <div x-show="editing" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/80 rounded-xl p-5 border border-indigo-100/50 shadow-sm mt-4">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h4 class="font-medium text-gray-900 flex items-center gap-2">
                    <i class="ri-edit-line text-indigo-500"></i>
                    Изменить номер телефона
                </h4>
                <button @click="editing = false; newPhone = '{{ Auth::user()->shop->phone ?? '' }}'" 
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            
            {{-- Форма сохранения номера --}}
            @include('seller.partials.phone.update-form')
            
            {{-- Разделитель --}}
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-indigo-100/50"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-indigo-50/80 text-indigo-500 rounded-lg">или</span>
                </div>
            </div>
            
            {{-- Форма отправки кода --}}
            @include('seller.partials.phone.verify-form')
        </div>
    </div>
</div>