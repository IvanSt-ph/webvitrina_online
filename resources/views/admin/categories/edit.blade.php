@extends('admin.layout')

@section('title', 'Редактировать категорию')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Редактировать категорию</h1>

    <!-- Форма обновления -->
    <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-medium">Название</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" class="w-full border rounded p-2" required>
        </div>

        <div>
            <label class="block font-medium">Slug</label>
            <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" class="w-full border rounded p-2" required>
        </div>

        <div>
            <label class="block font-medium">Родительская категория</label>
            <select name="parent_id" class="w-full border rounded p-2">
                <option value="">— Нет —</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" 
                        @selected(old('parent_id', $category->parent_id) == $parent->id)>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block font-medium">Иконка</label>
            @if($category->icon)
                <div class="mb-2">
                    <img src="{{ asset('storage/' . $category->icon) }}" 
                         alt="Иконка категории" 
                         class="h-12">
                </div>
            @endif
            <input type="file" name="icon" class="w-full border rounded p-2">
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">
                💾 Обновить
            </button>
        </div>
    </form>

    <!-- Форма удаления (отдельная) -->
    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" 
          class="mt-6"
          onsubmit="return confirm('Точно удалить категорию? Это действие нельзя отменить!');">
        @csrf
        @method('DELETE')
        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">
            🗑 Удалить категорию
        </button>
    </form>
@endsection
