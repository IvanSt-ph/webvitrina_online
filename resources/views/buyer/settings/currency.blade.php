<x-buyer-layout title="Выбор валюты">
    <div class="p-6 space-y-4">

        <p class="text-gray-500">Выберите валюту для показа цен:</p>

        <div class="bg-white rounded-xl border shadow-sm divide-y">

            <label class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🇲🇩</span>
                    <span>Moldovan Leu (MDL)</span>
                </div>
                <input type="radio" name="currency" checked>
            </label>

            <label class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🇺🇦</span>
                    <span>Ukrainian Hryvnia (UAH)</span>
                </div>
                <input type="radio" name="currency">
            </label>

            <label class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🇷🇺</span>
                    <span>PMR Ruble (RUB PMR)</span>
                </div>
                <input type="radio" name="currency">
            </label>

            <label class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">💵</span>
                    <span>US Dollar (USD)</span>
                </div>
                <input type="radio" name="currency">
            </label>

        </div>

        <button class="w-full mt-4 bg-indigo-600 text-white py-2 rounded-lg font-medium">
            Сохранить
        </button>

    </div>
</x-buyer-layout>
