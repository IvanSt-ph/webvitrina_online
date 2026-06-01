@extends('admin.layout')

@section('title','Редактировать категорию')

@section('content')
@php
  $chain = $chain ?? collect();
  $categoryPath = $chain->pluck('name')->push($category->name)->join(' / ');
  $level = $chain->count() + 1;
@endphp

<div class="space-y-5">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <a href="{{ route('admin.categories.index') }}"
       class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-indigo-600">
      <i class="ri-arrow-left-line"></i>
      Назад к категориям
    </a>

    <a href="{{ route('admin.categories.attributes', $category->id) }}"
       class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-100 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
      <i class="ri-equalizer-line"></i>
      Характеристики
    </a>
  </div>

  <section class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-white via-white to-indigo-50/70 p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
          <i class="ri-folder-settings-line"></i>
          Редактирование категории
        </div>
        <h1 class="mt-3 text-2xl font-bold text-gray-900">{{ $category->name }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-gray-500">{{ $categoryPath }}</p>
      </div>

      <div class="grid min-w-full gap-2 sm:grid-cols-3 lg:min-w-[420px]">
        <div class="rounded-2xl border border-white bg-white/80 p-4 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">ID</div>
          <div class="mt-1 text-xl font-bold text-slate-900">{{ $category->id }}</div>
        </div>
        <div class="rounded-2xl border border-white bg-white/80 p-4 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Уровень</div>
          <div class="mt-1 text-xl font-bold text-slate-900">{{ $level }}</div>
        </div>
        <div class="rounded-2xl border border-white bg-white/80 p-4 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Slug</div>
          <div class="mt-1 truncate text-sm font-bold text-slate-900">{{ $category->slug }}</div>
        </div>
      </div>
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

  <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.categories.form', [
        'category' => $category,
        'parents'  => $parents,
        'chain' => $chain,
        'blockedParentIds' => $blockedParentIds ?? collect(),
        'submit'   => 'Обновить категорию'
    ])
  </form>

  <section class="rounded-3xl border border-red-100 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h2 class="text-lg font-semibold text-red-700">Опасная зона</h2>
        <p class="mt-1 text-sm text-gray-500">
          Удаление уберёт эту категорию и вложенные категории. Используйте только если уверены, что структура больше не нужна.
        </p>
      </div>
      <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
            onsubmit="return confirm('Точно удалить категорию и все вложенные категории? Это действие нельзя отменить!');">
        @csrf @method('DELETE')
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
          <i class="ri-delete-bin-line"></i>
          Удалить категорию
        </button>
      </form>
    </div>
  </section>
</div>
@endsection
