@extends('admin.layout')

@section('title','Добавить категорию')

@section('content')
<h1 class="text-2xl font-bold mb-4">Добавить категорию</h1>

{{-- Ошибки --}}
@if ($errors->any())
  <div class="mb-4 p-4 rounded bg-red-100 text-red-700">
    <strong>Ошибки:</strong>
    <ul class="list-disc ml-5 mt-2">
      @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
  </div>
@endif

<form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @include('admin.categories.form', [
        'category' => new \App\Models\Category(),
        'parents'  => $parents,
        'submit'   => 'Сохранить'
    ])
</form>
@endsection
