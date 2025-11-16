<x-buyer-layout title="Язык интерфейса">
    <div class="p-6 space-y-4">

        <p class="text-gray-500">Выберите язык приложения:</p>

        <div class="bg-white rounded-xl border shadow-sm divide-y">

            <label class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🇷🇺</span>
                    <span>Русский</span>
                </div>
                <input type="radio" name="lang" checked>
            </label>

            <label class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🇬🇧</span>
                    <span>English</span>
                </div>
                <input type="radio" name="lang">
            </label>

            <label class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🇺🇦</span>
                    <span>Українська</span>
                </div>
                <input type="radio" name="lang">
            </label>

        </div>

        <button class="w-full mt-4 bg-indigo-600 text-white py-2 rounded-lg font-medium">
            Сохранить
        </button>

    </div>
</x-buyer-layout>
