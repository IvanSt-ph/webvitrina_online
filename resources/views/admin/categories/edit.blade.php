@extends('admin.layout')

@section('title','Редактировать категорию')

@section('content')
<h1 class="text-2xl font-bold mb-4">Редактировать категорию</h1>

@if ($errors->any())
  <div class="mb-4 p-4 rounded bg-red-100 text-red-700">
    <strong>Ошибки:</strong>
    <ul class="list-disc ml-5 mt-2">
      @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
  </div>
@endif

<form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @method('PUT')
    @include('admin.categories.form', [
        'category' => $category,
        'parents'  => $parents,
        'submit'   => 'Обновить'
    ])
</form>

{{-- Отдельная форма удаления (как было) --}}
<form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="mt-6"
      onsubmit="return confirm('Точно удалить категорию? Это действие нельзя отменить!');">
    @csrf @method('DELETE')
    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">🗑 Удалить категорию</button>
</form>
@endsection
