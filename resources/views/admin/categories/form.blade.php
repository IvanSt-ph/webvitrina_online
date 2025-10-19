@php
    /** @var \App\Models\Category $category */
    $category = $category ?? new \App\Models\Category();
    $submit   = $submit ?? 'Сохранить';
@endphp

{{-- 🏷️ Название + Slug --}}
<div class="grid md:grid-cols-2 gap-4" x-data="slugHelper()">
    <div>
        <label class="block text-sm font-medium text-gray-700">Название</label>
        <input type="text" name="name" x-model="title"
               value="{{ old('name', $category->name) }}"
               class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Slug</label>
        <div class="flex gap-2">
            <input type="text" name="slug" x-model="slug"
                   value="{{ old('slug', $category->slug) }}"
                   class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500" required>
            <button type="button" @click="makeSlug()"
                    class="mt-1 px-3 py-2 text-sm bg-gray-100 rounded hover:bg-gray-200">
                Сгенерировать
            </button>
        </div>
    </div>
</div>

{{-- 🧭 Родительская категория (каскадно) --}}
<div x-data="categorySelect()" x-init="init()" class="mt-6 space-y-2">
    <label class="block text-sm font-medium text-gray-700">Родительская категория</label>

    <div id="category-selects" class="space-y-2">
        <select @change="loadChildren($event, 0)"
                name="categories[0]"
                class="w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">— Нет —</option>
            @foreach($parents->whereNull('parent_id') as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>
                    {{ $parent->name }}
                </option>
            @endforeach
        </select>
    </div>

    <input type="hidden" name="parent_id" x-model="finalCategory"
           value="{{ old('parent_id', $category->parent_id) }}">
</div>

{{-- 🖼 Изображение для плитки --}}
<div class="mt-8 border-t pt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-3">🖼 Изображение для плитки</h3>
    @if($category->image)
        <div class="mb-3">
            <p class="text-sm text-gray-500 mb-1">Текущее изображение:</p>
            <img src="{{ asset('storage/'.$category->image) }}" alt="image"
                 class="w-32 h-32 object-cover rounded-lg border shadow-sm">
        </div>
    @endif
    <input type="file" name="image" class="block w-full text-sm border rounded-lg p-2 focus:ring-indigo-500 focus:border-indigo-500">
    <p class="text-xs text-gray-500 mt-1">Используется как картинка плитки на странице категорий.</p>
</div>

{{-- 🔖 Иконка для меню --}}
<div class="mt-8 border-t pt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-3">🔖 Иконка для меню</h3>
    @if($category->icon)
        <div class="mb-3">
            <p class="text-sm text-gray-500 mb-1">Текущая иконка:</p>
            <img src="{{ asset('storage/'.$category->icon) }}" alt="icon"
                 class="w-16 h-16 object-contain opacity-90 rounded">
        </div>
    @endif
    <input type="file" name="icon" class="block w-full text-sm border rounded-lg p-2 focus:ring-indigo-500 focus:border-indigo-500">
    <p class="text-xs text-gray-500 mt-1">Используется в навигации, боковом меню или заголовках категорий.</p>
</div>

{{-- 💾 Кнопка --}}
<div class="mt-8 border-t pt-6">
    <button type="submit"
            class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
        💾 {{ $submit }}
    </button>
</div>

{{-- ⚙️ JS helpers --}}
<script>
function slugHelper() {
    return {
        title: @json(old('name', $category->name)),
        slug:  @json(old('slug', $category->slug)),
        makeSlug() {
            const map={а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'e',ж:'zh',з:'z',и:'i',й:'y',к:'k',л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'h',ц:'c',ч:'ch',ш:'sh',щ:'sch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya'};
            let s=(this.slug||this.title||'').toString().trim().toLowerCase();
            s=s.replace(/[\u0400-\u04FF]/g,ch=>map[ch]??ch)
               .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').substring(0,80);
            this.slug=s;
        }
    }
}

function categorySelect() {
    return {
        finalCategory: '{{ old('parent_id', $category->parent_id) }}',
        async init() {
            if (this.finalCategory) await this.loadChain(this.finalCategory);
        },
        async loadChildren(event, level) {
            const parentId = event.target.value;
            this.finalCategory = parentId;
            document.querySelectorAll('#category-selects select').forEach((el, i) => {
                if (i > level) el.remove();
            });
            if (!parentId) return;
            const res = await fetch(`/categories/${parentId}/children`);
            const data = await res.json();
            if (data.length > 0) {
                const select = document.createElement('select');
                select.name = `categories[${level + 1}]`;
                select.className = 'w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500';
                select.innerHTML = `<option value="">— Выберите подкатегорию —</option>`;
                data.forEach(cat => select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`);
                select.addEventListener('change', (e) => this.loadChildren(e, level + 1));
                document.getElementById('category-selects').appendChild(select);
            }
        },
        async loadChain(categoryId) {
            let current = categoryId, chain = [];
            while (current) {
                const res = await fetch(`/categories/${current}/parent`);
                const data = await res.json();
                if (data.parent_id) { chain.unshift(data.parent_id); current = data.parent_id; } else break;
            }
            let level = 0;
            for (const id of chain) {
                const res = await fetch(`/categories/${id}/children`);
                const data = await res.json();
                if (data.length > 0) {
                    const select = document.createElement('select');
                    select.name = `categories[${level}]`;
                    select.className = 'w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500';
                    select.innerHTML = `<option value="">— Выберите —</option>`;
                    data.forEach(cat => select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`);
                    select.addEventListener('change', (e) => this.loadChildren(e, level));
                    document.getElementById('category-selects').appendChild(select);
                    select.value = id;
                    level++;
                }
            }
        }
    }
}
</script>
