@extends('admin.layout')

@section('title','Добавить категорию')

@section('content')
<div class="space-y-5">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <a href="{{ route('admin.categories.index') }}"
       class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-indigo-600">
      <i class="ri-arrow-left-line"></i>
      Назад к категориям
    </a>
  </div>

  <section class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-white via-white to-indigo-50/70 p-5 shadow-sm">
    <div class="max-w-3xl">
      <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
        <i class="ri-folder-add-line"></i>
        Новая категория
      </div>
      <h1 class="mt-3 text-2xl font-bold text-gray-900">Добавить категорию</h1>
      <p class="mt-1 text-sm text-gray-500">
        Выберите место в дереве: без родителя это будет корневой раздел, с родителем — подкатегория.
      </p>
    </div>
  </section>

  @if ($errors->any())
    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700">
      <strong>Нужно поправить:</strong>
      <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('admin.categories.form', [
        'category' => new \App\Models\Category(),
        'parents'  => $parents,
        'blockedParentIds' => $blockedParentIds ?? collect(),
        'submit'   => 'Сохранить категорию'
    ])
  </form>
</div>
@endsection
