<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700">Иконка категории</label>
    <input type="file" name="icon" class="mt-1 block w-full border rounded p-2">
    @if($category->icon ?? false)
        <img src="{{ asset('storage/' . $category->icon) }}" alt="icon" class="w-8 h-8 mt-2">
    @endif
</div>
