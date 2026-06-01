@php
    /** @var \App\Models\Category $category */
    $category = $category ?? new \App\Models\Category();
    $submit = $submit ?? 'Сохранить';
    $chain = $chain ?? collect();
    $blockedParentIds = collect($blockedParentIds ?? []);
    $isEdit = $category->exists;
@endphp

<div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
  <div class="space-y-5">
    <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm" x-data="slugHelper()">
      <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Основное</h2>
        <p class="text-sm text-gray-500">Название видно покупателю, slug используется в ссылках и должен быть понятным.</p>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Название категории</label>
          <input type="text" name="name" x-model="title"
                 value="{{ old('name', $category->name) }}"
                 class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500"
                 placeholder="Например: Смарт-часы" required>
          <p class="mt-1 text-xs text-gray-400">Лучше коротко: 1-3 слова.</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Slug</label>
          <div class="flex gap-2">
            <input type="text" name="slug" x-model="slug"
                   value="{{ old('slug', $category->slug) }}"
                   class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500"
                   placeholder="smart-chasy" required>
            <button type="button" @click="makeSlug()"
                    class="shrink-0 rounded-xl bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
              Сгенерировать
            </button>
          </div>
          <p class="mt-1 text-xs text-gray-400">Если меняете slug у живой категории, старые ссылки могут измениться.</p>
        </div>
      </div>
    </section>

    <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
      <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Место в дереве</h2>
          <p class="text-sm text-gray-500">Оставьте пустым, если категория должна быть корневым разделом.</p>
        </div>
        @if($isEdit)
          <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
            <i class="ri-shield-check-line"></i>
            Нельзя вложить в себя
          </span>
        @endif
      </div>

      <div x-data="categorySelect()" x-init="init()" class="space-y-3">
        <div id="category-selects" class="grid gap-3 md:grid-cols-2"></div>

        <input type="hidden" name="parent_id" x-model="finalCategory"
               value="{{ old('parent_id', $category->parent_id) }}">

        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
          <i class="ri-information-line mr-1"></i>
          Товары лучше привязывать к конечным категориям. Родительские разделы нужны для навигации.
        </div>
      </div>
    </section>

    <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
      <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Изображения</h2>
        <p class="text-sm text-gray-500">Картинка используется в плитках, иконка — в меню и быстрых разделах.</p>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 p-4" x-data="imagePreview(@js($category->image ? asset('storage/'.$category->image) : null))">
          <div class="flex items-start gap-4">
            <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
              <template x-if="preview">
                <img :src="preview" alt="" class="h-full w-full object-cover">
              </template>
              <template x-if="!preview">
                <i class="ri-image-line text-3xl text-slate-300"></i>
              </template>
            </div>
            <div class="min-w-0 flex-1">
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Изображение плитки</label>
              <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                     @change="pick($event)"
                     class="block w-full rounded-xl border border-gray-300 p-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500">
              <p class="mt-2 text-xs text-gray-400">JPG, PNG или WebP. SVG не принимается. Сохраняется в WebP.</p>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 p-4" x-data="imagePreview(@js($category->icon ? asset('storage/'.$category->icon) : null))">
          <div class="flex items-start gap-4">
            <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
              <template x-if="preview">
                <img :src="preview" alt="" class="h-16 w-16 object-contain">
              </template>
              <template x-if="!preview">
                <i class="ri-folder-image-line text-3xl text-slate-300"></i>
              </template>
            </div>
            <div class="min-w-0 flex-1">
              <label class="mb-1.5 block text-sm font-semibold text-gray-700">Иконка меню</label>
              <input type="file" name="icon" accept="image/jpeg,image/png,image/webp"
                     @change="pick($event)"
                     class="block w-full rounded-xl border border-gray-300 p-2 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500">
              <p class="mt-2 text-xs text-gray-400">Лучше квадратная картинка с прозрачным или светлым фоном.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Сохранение</h2>
          <p class="text-sm text-gray-500">После сохранения кеш категорий и фильтров будет обновлён автоматически.</p>
        </div>
        <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
          <i class="ri-save-3-line"></i>
          {{ $submit }}
        </button>
      </div>
    </section>
  </div>

  <aside class="space-y-4">
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <h3 class="font-semibold text-slate-900">Как выбрать уровень</h3>
      <div class="mt-4 space-y-3 text-sm text-slate-600">
        <p><span class="font-semibold text-slate-900">Корень</span> — крупный раздел в меню: Электроника, Одежда.</p>
        <p><span class="font-semibold text-slate-900">Ветка</span> — группа внутри раздела: Носимая электроника.</p>
        <p><span class="font-semibold text-slate-900">Конечная</span> — место для товаров и характеристик: Смарт-часы.</p>
      </div>
    </div>

    <div class="rounded-3xl border border-indigo-100 bg-indigo-50 p-5 text-sm text-indigo-900">
      <div class="flex items-center gap-2 font-semibold">
        <i class="ri-compass-3-line"></i>
        UX-подсказка
      </div>
      <p class="mt-2">Если категория нужна покупателю в фильтрах и карточках товара, лучше держать её на нижнем уровне и добавить характеристики.</p>
    </div>

    @if($isEdit)
      <div class="rounded-3xl border border-amber-100 bg-amber-50 p-5 text-sm text-amber-900">
        <div class="flex items-center gap-2 font-semibold">
          <i class="ri-alert-line"></i>
          Перед переносом
        </div>
        <p class="mt-2">Смена родителя меняет путь категории в админке и навигации. Товары останутся в этой категории.</p>
      </div>
    @endif
  </aside>
</div>

<script>
function slugHelper() {
    return {
        title: @json(old('name', $category->name)),
        slug: @json(old('slug', $category->slug)),
        makeSlug() {
            const map = {а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'e',ж:'zh',з:'z',и:'i',й:'y',к:'k',л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'h',ц:'c',ч:'ch',ш:'sh',щ:'sch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya'};
            let s = (this.slug || this.title || '').toString().trim().toLowerCase();
            s = s.replace(/[\u0400-\u04FF]/g, ch => map[ch] ?? ch)
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .substring(0, 80);
            this.slug = s;
        }
    }
}

function imagePreview(initial) {
    return {
        preview: initial,
        pick(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.preview = URL.createObjectURL(file);
        }
    }
}

function categorySelect() {
    return {
        finalCategory: @json((string) old('parent_id', $category->parent_id)),
        chain: @json($chain->pluck('id')->values()),
        blocked: @json($blockedParentIds->values()->map(fn ($id) => (int) $id)),
        initialized: false,

        async init() {
            if (this.initialized) return;
            this.initialized = true;

            if (this.chain.length > 0) {
                await this.buildChain();
            } else {
                const roots = await this.fetchRoot();
                document.getElementById('category-selects').appendChild(this.createSelect(0, roots));
            }
        },

        async buildChain() {
            let prevId = null;
            for (let i = 0; i < this.chain.length; i++) {
                const data = prevId ? await this.fetchChildren(prevId) : await this.fetchRoot();
                const select = this.createSelect(i, data);
                document.getElementById('category-selects').appendChild(select);
                select.value = this.chain[i];
                prevId = this.chain[i];
            }

            if (prevId) {
                const lastChildren = await this.fetchChildren(prevId);
                if (lastChildren.length > 0) {
                    document.getElementById('category-selects').appendChild(this.createSelect(this.chain.length, lastChildren));
                }
            }

            this.finalCategory = prevId ? String(prevId) : '';
        },

        async loadChildren(event, level) {
            const parentId = event.target.value;
            this.finalCategory = parentId;

            document.querySelectorAll('#category-selects select').forEach((el, i) => {
                if (i > level) el.remove();
            });

            if (!parentId) return;

            const data = await this.fetchChildren(parentId);
            if (data.length > 0) {
                document.getElementById('category-selects').appendChild(this.createSelect(level + 1, data));
            }
        },

        async fetchRoot() {
            const res = await fetch('/admin/categories/root');
            return res.json();
        },

        async fetchChildren(id) {
            const res = await fetch(`/admin/categories/${id}/children`);
            return res.json();
        },

        createSelect(level, data) {
            const select = document.createElement('select');
            select.name = `categories[${level}]`;
            select.className = 'w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500';
            select.add(new Option(level === 0 ? '— Корневая категория —' : '— Оставить на этом уровне —', ''));

            data.forEach(cat => {
                const option = new Option(cat.name, cat.id);
                if (this.blocked.includes(Number(cat.id))) {
                    option.disabled = true;
                    option.text = `${cat.name} — нельзя выбрать`;
                }
                select.add(option);
            });

            select.addEventListener('change', (e) => this.loadChildren(e, level));
            return select;
        }
    }
}
</script>
