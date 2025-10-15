@php
    /** @var \App\Models\Category $category */
    $category = $category ?? new \App\Models\Category();
    $submit   = $submit ?? 'Сохранить';
@endphp

{{-- Название + slug --}}
<div class="grid md:grid-cols-2 gap-4" x-data="slugHelper()">
    <div>
        <label class="block text-sm font-medium text-gray-700">Название</label>
        <input type="text" name="name" x-model="title"
               value="{{ old('name', $category->name) }}"
               class="mt-1 block w-full border rounded-lg px-3 py-2" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Slug</label>
        <div class="flex gap-2">
            <input type="text" name="slug" x-model="slug"
                   value="{{ old('slug', $category->slug) }}"
                   class="mt-1 block w-full border rounded-lg px-3 py-2" required>
            <button type="button" @click="makeSlug()"
                    class="mt-1 px-3 py-2 text-sm bg-gray-100 rounded hover:bg-gray-200">
                Сгенерировать
            </button>
        </div>
    </div>
</div>

{{-- Родительская категория (каскадно) --}}
<div x-data="categorySelect()" x-init="init()" class="mt-6 space-y-2">
    <label class="block text-sm font-medium text-gray-700">Родительская категория</label>

    <div id="category-selects" class="space-y-2">
        {{-- Только корневые категории --}}
        <select @change="loadChildren($event, 0)"
                name="categories[0]"
                class="w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">— Нет —</option>
            @foreach($parents->whereNull('parent_id') as $parent)
                <option value="{{ $parent->id }}"
                    {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                    {{ $parent->name }}
                </option>
            @endforeach
        </select>
    </div>

    <input type="hidden" name="parent_id" x-model="finalCategory"
           value="{{ old('parent_id', $category->parent_id) }}">
</div>

{{-- Иконка --}}
<div class="mt-6">
    <label class="block text-sm font-medium text-gray-700">Иконка</label>
    @if($category->icon)
        <div class="mb-2">
            <img src="{{ asset('storage/'.$category->icon) }}" alt="icon" class="h-12 rounded border">
        </div>
    @endif
    <input type="file" name="icon" class="mt-1 block w-full border rounded p-2">
</div>

{{-- Кнопка --}}
<div class="mt-6">
    <button type="submit"
            class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
        {{ $submit }}
    </button>
</div>

{{-- JS helpers --}}
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
            if (this.finalCategory) {
                await this.loadChain(this.finalCategory);
            }
        },
        async loadChildren(event, level) {
            const parentId = event.target.value;
            this.finalCategory = parentId;

            // удаляем селекты ниже текущего уровня
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
                data.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                });
                select.addEventListener('change', (e) => this.loadChildren(e, level + 1));
                document.getElementById('category-selects').appendChild(select);
            }
        },
        async loadChain(categoryId) {
            let current = categoryId;
            let chain = [];

            // получаем цепочку родителей
            while (current) {
                const res = await fetch(`/categories/${current}/parent`);
                const data = await res.json();
                if (data.parent_id) {
                    chain.unshift(data.parent_id);
                    current = data.parent_id;
                } else break;
            }

            // строим выпадающие списки по цепочке
            let level = 0;
            for (const id of chain) {
                const res = await fetch(`/categories/${id}/children`);
                const data = await res.json();
                if (data.length > 0) {
                    const select = document.createElement('select');
                    select.name = `categories[${level}]`;
                    select.className = 'w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500';
                    select.innerHTML = `<option value="">— Выберите —</option>`;
                    data.forEach(cat => {
                        select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                    });
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
