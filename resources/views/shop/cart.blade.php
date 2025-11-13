{{-- resources/views/shop/cart.blade.php --}}
<x-buyer-layout title="Моя корзина">

<div x-data="cartSelection()" class="space-y-6">

    <!-- 🔝 Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl sm:text-3xl font-semibold">🛒 Моя корзина</h1>
            <p class="text-gray-500 text-sm">Проверьте товары перед оформлением</p>
        </div>

        @if($items->isNotEmpty())
        <div class="flex items-center gap-3">

            <!-- Выбор -->
            <button @click="selectMode = !selectMode; if(!selectMode) selected=[];"
                class="px-4 py-2 border border-indigo-400 text-indigo-600 rounded-lg text-sm">
                <span x-show="!selectMode">Выбрать товары</span>
                <span x-show="selectMode">Отменить выбор</span>
            </button>

            <!-- Оформить всё -->
            <form method="POST" action="{{ route('checkout.prepare') }}">
                @csrf
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">
                    Оформить всю корзину
                </button>
            </form>

        </div>
        @endif
    </div>

    @if($items->isEmpty())

        <!-- 🕊 Пустая корзина -->
        <div class="text-center py-24">
            <div class="text-6xl mb-3">🛍️</div>
            <p class="text-lg">Ваша корзина пуста</p>

            <a href="{{ route('home') }}"
               class="mt-6 inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg">
                Перейти в каталог
            </a>
        </div>

    @else

    <!-- 📜 СПИСОК -->
    <div class="flex flex-col divide-y divide-gray-200">

        @foreach($items as $i)
        @php $p = $i->product; @endphp

        <div 
            x-data="{ qty: {{ $i->qty }} }"
            class="flex gap-3 py-3 cursor-pointer relative"
            @click="
                if(selectMode){
                    const id='{{ $i->id }}';
                    selected.includes(id)
                        ? selected = selected.filter(x=>x!==id)
                        : selected.push(id)
                }
            "
            :class="selected.includes('{{ $i->id }}') ? 'bg-indigo-50' : ''"
        >

            <!-- Чекбокс -->
            <div x-show="selectMode" class="flex items-start pt-1" @click.stop>
                <input 
                    type="checkbox" 
                    x-model="selected" 
                    value="{{ $i->id }}" 
                    class="w-4 h-4 text-indigo-600 rounded">
            </div>

            <!-- Фото -->
            <a href="{{ route('product.show',$p) }}"
                class="w-[70px] h-[70px] rounded-lg overflow-hidden border flex-shrink-0
                       transition"
                :class="selectMode ? 'opacity-50 pointer-events-none' : ''"
            >
                @if($p->image)
                    <img src="{{ asset('storage/'.$p->image) }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full bg-gray-100 flex items-center justify-center text-xl text-gray-400">📦</div>
                @endif
            </a>

            <!-- Информация -->
            <div class="flex flex-col flex-1">

                <!-- Название -->
                <a href="{{ route('product.show',$p) }}"
                   class="text-sm font-medium line-clamp-2"
                   :class="selectMode ? 'opacity-60 pointer-events-none' : ''">
                    {{ $p->title }}
                </a>

                <!-- Цена -->
                <div class="text-[16px] font-semibold mt-1">
                    {{ number_format($p->price,2,',',' ') }} ₽
                </div>

                <!-- Управление -->
                <div class="flex items-center justify-between mt-3"
                     :class="selectMode ? 'opacity-50 pointer-events-none' : ''">

                    <!-- Количество -->
                    <input type="number" min="1" 
                           x-model="qty"
                           class="w-14 h-8 border rounded text-center text-xs">

                    <div class="flex items-center gap-2">

                        <!-- Купить -->
                        <form method="POST" action="{{ route('checkout.quick',$p->id) }}">
                            @csrf
                            <input type="hidden" name="qty" :value="qty">
                            <button class="px-3 py-1 bg-indigo-600 text-white text-xs rounded">Купить</button>
                        </form>

                        <!-- Удалить -->
                        <form method="POST" action="{{ route('cart.remove',$i) }}">
                            @csrf @method('DELETE')
                            <button class="px-3 py-1 border text-xs rounded text-gray-600 hover:text-red-600">
                                Удалить
                            </button>
                        </form>

                    </div>

                </div>

            </div>

        </div>

        @endforeach

    </div>

    <!-- Итог выбранных -->
    <template x-if="selectMode">
        <div class="bg-white rounded-2xl border border-gray-200 p-5 mt-6 flex items-center justify-between">

            <div>
                <div class="text-sm text-gray-500">Выбрано товаров:</div>
                <div class="text-3xl font-semibold"><span x-text="selected.length"></span></div>
            </div>

            <form method="POST" action="{{ route('checkout.prepare') }}">
                @csrf

                <template x-for="id in selected">
                    <input type="hidden" name="selected_items[]" :value="id">
                </template>

                <button :disabled="selected.length===0"
                        class="px-6 py-3 bg-indigo-600 disabled:bg-gray-300 text-white text-sm rounded-lg">
                    Оформить выбранные
                </button>
            </form>

        </div>
    </template>

    @endif

</div>

<script>
function cartSelection(){
    return { selectMode:false, selected:[] }
}
</script>

</x-buyer-layout>
