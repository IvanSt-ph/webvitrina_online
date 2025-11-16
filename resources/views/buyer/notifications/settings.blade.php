<x-buyer-layout title="Уведомления">
    <div class="p-6 space-y-4">

        <p class="text-gray-500 text-sm">
            Здесь вы сможете включать и отключать уведомления.
        </p>

        <div class="bg-white p-4 rounded-xl border shadow-sm space-y-3">
            <label class="flex items-center justify-between">
                <span>Email уведомления</span>
                <input type="checkbox" class="toggle" checked>
            </label>

            <label class="flex items-center justify-between">
                <span>SMS уведомления</span>
                <input type="checkbox" class="toggle">
            </label>

            <label class="flex items-center justify-between">
                <span>Push уведомления</span>
                <input type="checkbox" class="toggle">
            </label>
        </div>

    </div>
</x-buyer-layout>
