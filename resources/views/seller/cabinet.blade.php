<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Кабинет продавца') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Мои товары -->
                <a href="{{ route('seller.products.index') }}" 
                   class="group bg-white shadow rounded-xl p-6 flex flex-col items-center justify-center 
                          hover:shadow-lg hover:bg-indigo-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                         class="h-12 w-12 text-indigo-600 group-hover:scale-110 transition-transform" 
                         fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 3h18v2H3V3zm2 4h14v2H5V7zm-2 4h18v10H3V11zm2 2v6h14v-6H5z"/>
                    </svg>
                    <span class="mt-4 text-lg font-semibold text-gray-800">Мои товары</span>
                    <span class="text-sm text-gray-500">Управление товарами</span>
                </a>

                <!-- Добавить товар -->
                <a href="{{ route('seller.products.create') }}" 
                   class="group bg-white shadow rounded-xl p-6 flex flex-col items-center justify-center 
                          hover:shadow-lg hover:bg-green-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                         class="h-12 w-12 text-green-600 group-hover:scale-110 transition-transform" 
                         fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 5v14m-7-7h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span class="mt-4 text-lg font-semibold text-gray-800">Добавить товар</span>
                    <span class="text-sm text-gray-500">Создать новую карточку</span>
                </a>

                <!-- Настройки профиля -->
                <a href="{{ route('profile.edit') }}" 
                   class="group bg-white shadow rounded-xl p-6 flex flex-col items-center justify-center 
                          hover:shadow-lg hover:bg-yellow-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                         class="h-12 w-12 text-yellow-500 group-hover:scale-110 transition-transform" 
                         fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14a2 2 0 100-4 2 2 0 000 4zm0 7a7 7 0 100-14 7 7 0 000 14z"/>
                    </svg>
                    <span class="mt-4 text-lg font-semibold text-gray-800">Профиль</span>
                    <span class="text-sm text-gray-500">Личные данные и настройки</span>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
