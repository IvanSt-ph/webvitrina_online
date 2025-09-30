@extends('admin.layout')

@section('title', 'Добавить категорию')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Добавить категорию</h1>

    <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label class="block font-medium">Название</label>
            <input type="text" name="name" class="w-full border rounded p-2" required>
        </div>

        <div>
            <label class="block font-medium">Slug</label>
            <input type="text" name="slug" class="w-full border rounded p-2" required>
        </div>

        <div>
            <label class="block font-medium">Родительская категория</label>
            <select name="parent_id" class="w-full border rounded p-2">
                <option value="">— Нет —</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block font-medium">Иконка</label>
            <input type="file" name="icon" class="w-full border rounded p-2">
        </div>

        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Сохранить</button>
    </form>
@endsection
