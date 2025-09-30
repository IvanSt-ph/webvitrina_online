<!-- Выдвижное боковое меню категорий -->
<div 
    x-show="open"
    class="fixed inset-0 z-50 flex"
    x-cloak
>
    <!-- overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity duration-300"
         x-show="open"
         x-transition.opacity
         @click="open = false"></div>

    <!-- sidebar -->
    <div 
        class="relative bg-white w-72 h-full shadow-lg z-50 overflow-y-auto transform transition-transform duration-300"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
    >
        <!-- header -->
        <div class="flex items-center justify-between px-4 py-3 border-b">
            <h2 class="text-lg font-semibold">Категории</h2>
            <button @click="open = false" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>

        <!-- список категорий -->
        <ul class="divide-y divide-gray-200">
            @foreach($categories as $cat)
                <x-category-item :category="$cat" />
            @endforeach
        </ul>
    </div>
</div>
