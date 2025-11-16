<x-buyer-layout title="Стать продавцом">
    <div class="p-6 space-y-4">

        <p class="text-gray-600">
            Хотите продавать товары на WebVitrina? Это можно сделать в несколько шагов.
        </p>

        <div class="bg-white rounded-xl border shadow-sm p-4 space-y-3">

            <div class="flex items-start gap-3">
                <i class="ri-store-2-line text-2xl text-indigo-600"></i>
                <div>
                    <p class="font-medium">Создайте магазин</p>
                    <p class="text-sm text-gray-500">Укажите страну, город и основные данные магазина.</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <i class="ri-briefcase-2-line text-2xl text-blue-500"></i>
                <div>
                    <p class="font-medium">Добавьте товары</p>
                    <p class="text-sm text-gray-500">Загрузите товары, фотографий, описание, цену и атрибуты.</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <i class="ri-customer-service-2-line text-2xl text-green-600"></i>
                <div>
                    <p class="font-medium">Получайте заказы</p>
                    <p class="text-sm text-gray-500">Управляйте заказами, общайтесь с покупателями и развивайте продажи.</p>
                </div>
            </div>

        </div>

        <a href="{{ route('register') }}"
           class="block bg-indigo-600 text-white text-center py-3 rounded-lg font-medium">
            Перейти к регистрации продавца
        </a>

    </div>
</x-buyer-layout>
