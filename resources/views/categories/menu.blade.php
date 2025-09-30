<div class="p-4">
  <h2 class="text-lg font-bold mb-2">Категории</h2>
  <ul>
    @foreach($categories as $cat)
      <x-category-item :category="$cat" :activeCategoryId="$activeCategoryId" />
    @endforeach
  </ul>
</div>
