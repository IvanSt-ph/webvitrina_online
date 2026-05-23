{{-- resources/views/profile/partials/success-notification.blade.php --}}
@if (session('status') === 'profile-updated')
    <div x-data="{ show: true }" 
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                <i class="ri-check-line text-emerald-600"></i>
            </div>
            <div class="flex-1">
                <p class="font-medium text-emerald-800">Профиль обновлён!</p>
                <p class="text-sm text-emerald-600">Изменения сохранены успешно</p>
            </div>
            <button @click="show = false" class="text-emerald-400 hover:text-emerald-600">
                <i class="ri-close-line text-lg"></i>
            </button>
        </div>
    </div>
    
@endif
