{{-- resources/views/seller/products/form.blade.php --}}
<x-app-layout :title="$product->exists ? 'Редактирование' : 'Добавление'">
  <h1 class="text-2xl font-bold mb-4">
    {{ $product->exists ? 'Редактирование' : 'Добавление' }} товара
  </h1>

  {{-- Форма добавления / редактирования товара --}}
  <form method="post" enctype="multipart/form-data"
        action="{{ $product->exists ? route('seller.products.update',$product) : route('seller.products.store') }}"
        class="max-w-2xl bg-white border rounded p-4 space-y-3">
    @csrf
    @if($product->exists) @method('PUT') @endif

    {{-- === Название === --}}
    <div>
      <label class="block text-sm">Название</label>
      <input name="title" value="{{ old('title',$product->title) }}" class="w-full border rounded p-2"/>
      @error('title') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- === Цена и остаток === --}}
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm">Цена (в рублях)</label>
        <input name="price" type="number" step="0.01" min="0"
               value="{{ old('price',$product->price) }}" class="w-full border rounded p-2"/>
        @error('price') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="block text-sm">Остаток</label>
        <input name="stock" type="number" min="0"
               value="{{ old('stock',$product->stock) }}" class="w-full border rounded p-2"/>
        @error('stock') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
      </div>
    </div>

    {{-- === Описание === --}}
    <div>
      <label class="block text-sm">Описание</label>
      <textarea name="description" class="w-full border rounded p-2">{{ old('description',$product->description) }}</textarea>
      @error('description') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- === Категории (каскад) === --}}
    <div id="categories-wrapper">
      <label class="block text-sm">Категория</label>
    <select id="category-root" name="category_id" class="w-full border rounded p-2">

          <option value="">-- выберите категорию --</option>
          @foreach(($rootCategories ?? []) as $cat)
              <option value="{{ $cat->id }}"
                @selected(old('category_level_1', $product->category_id ?? null) == $cat->id)>
                  {{ $cat->name }}
              </option>
          @endforeach
      </select>
    </div>

    {{-- === Страна === --}}
    <div>
      <label class="block text-sm">Страна</label>
      <select id="country" name="country_id" class="w-full border rounded p-2">
          <option value="">-- выберите страну --</option>
          @foreach($countries as $country)
              <option value="{{ $country->id }}"
                @selected(old('country_id', optional($product->city)->country_id) == $country->id)>
                {{ $country->name }}
              </option>
          @endforeach
      </select>
      @error('country_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- === Город === --}}
    <div>
      <label class="block text-sm">Город</label>
      <select id="city" name="city_id" class="w-full border rounded p-2">
          <option value="">-- выберите город --</option>
      </select>
      @error('city_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- === Главное изображение === --}}
    <div>
      <label class="block text-sm">Изображение (главное фото)</label>
      <input type="file" name="image" class="w-full border rounded p-2"/>
      @if($product->image)
        <img src="{{ asset('storage/'.$product->image) }}" class="mt-2 w-40 rounded"/>
      @endif
      @error('image') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- === Галерея (несколько фото) === --}}
    <div>
      <label class="block text-sm">Галерея</label>
      <input type="file" name="gallery[]" multiple class="w-full border rounded p-2"/>

      {{-- Показываем уже загруженные фото при редактировании --}}
      @if(is_array($product->gallery) && count($product->gallery))
        <div class="flex gap-2 mt-2 flex-wrap">
          @foreach($product->gallery as $img)
            <img src="{{ asset('storage/'.$img) }}" class="w-20 h-20 rounded border"/>
          @endforeach
        </div>
      @endif

      @error('gallery') 
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p> 
      @enderror
    </div>

    {{-- === Кнопка сохранить === --}}
    <div class="pt-2">
      <button class="px-4 py-2 bg-emerald-600 text-white rounded">Сохранить</button>
    </div>
  </form>

  {{-- === JS для каскадных категорий и загрузки городов === --}}
  <script>
  document.addEventListener('DOMContentLoaded', () => {
      // Каскадные категории
      const wrapper = document.getElementById('categories-wrapper');
      async function fetchChildren(parentId) {
          if (!parentId) return [];
          const res = await fetch(`{{ url('/categories') }}/${parentId}/children`);
          return res.ok ? res.json() : [];
      }
      function createSelect(level, placeholder) {
          const div = document.createElement('div');
          div.className = "mt-2";
          const label = document.createElement('label');
          label.className = 'block text-sm';
          label.textContent = placeholder;
          const select = document.createElement('select');
          select.className = 'w-full border rounded p-2 mt-1';
          select.name = `category_level_${level}`;
          select.innerHTML = `<option value="">-- выберите --</option>`;
          div.appendChild(label);
          div.appendChild(select);
          wrapper.appendChild(div);
          return select;
      }
      wrapper.addEventListener('change', async (e) => {
          if (e.target.tagName !== 'SELECT') return;
          let currentDiv = e.target.closest('div');
          const allDivs = Array.from(wrapper.querySelectorAll('div'));
          const index = allDivs.indexOf(currentDiv);
          allDivs.slice(index + 1).forEach(div => div.remove());
          const children = await fetchChildren(e.target.value);
          if (children.length > 0) {
              const nextLevel = wrapper.querySelectorAll('select').length + 1;
              const placeholders = ['Категория','Подкатегория','Под-подкатегория','Под-уровень'];
              const select = createSelect(nextLevel, placeholders[nextLevel - 1] || `Уровень ${nextLevel}`);
              children.forEach(c => {
                  const opt = document.createElement('option');
                  opt.value = c.id;
                  opt.textContent = c.name;
                  select.appendChild(opt);
              });
          }
      });

      // AJAX Страна/Город
      const countrySelect = document.getElementById('country');
      const citySelect = document.getElementById('city');

      const preselectedCountryId = "{{ old('country_id', optional($product->city)->country_id) }}";
      const preselectedCityId    = "{{ old('city_id', $product->city_id) }}";

      async function loadCities(countryId, selectedCityId = null) {
          citySelect.innerHTML = '<option value="">-- выберите город --</option>';
          if (!countryId) return;
          const res = await fetch(`/countries/${countryId}/cities`);
          if (!res.ok) return;
          const cities = await res.json();
          cities.forEach(c => {
              const opt = document.createElement('option');
              opt.value = c.id;
              opt.textContent = c.name;
              if (selectedCityId && String(selectedCityId) === String(c.id)) {
                  opt.selected = true;
              }
              citySelect.appendChild(opt);
          });
      }

      countrySelect.addEventListener('change', () => loadCities(countrySelect.value, null));

      if (preselectedCountryId) {
          countrySelect.value = preselectedCountryId;
          loadCities(preselectedCountryId, preselectedCityId);
      }
  });
  </script>
</x-app-layout>
