@extends('admin.layout')
@section('title', 'Импорт характеристик')
@section('content')
<div class="space-y-6" x-data="{ status: '' }">
    <div class="rounded-2xl bg-white border border-indigo-100 p-6 space-y-3">
        <h1 class="text-2xl font-semibold text-gray-900">Импорт характеристик из Excel</h1>
        <p class="text-sm text-gray-600">Один лист, до 5000 строк и 2 МиБ. Только конечные категории и типы text, number, select. Группа отображается в отчёте, но не сохраняется.</p>
        <a class="text-indigo-600 underline" href="{{ route('admin.attribute-import.template') }}">Скачать шаблон XLSX</a>
        <p class="text-sm text-gray-600">Сохраните названия и порядок колонок. is_filterable: 0 или 1; options для select: JSON-массив, например ["S","M"]. Формулы и ссылки запрещены.</p>
        @if($errors->any())
            <ul class="text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        @isset($failure)<p role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">{{ $failure }}</p>@endisset
        @if($applied ?? false)<p role="status" class="rounded-xl bg-green-50 p-4 text-green-800">Импорт успешно применён. Ниже — отчёт выполненного плана.</p>@endif
        @foreach($report['warnings'] ?? [] as $warning)<p role="alert" class="text-amber-800">{{ $warning }}</p>@endforeach
        <form method="POST" action="{{ route('admin.attribute-import.preview') }}" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-center">
            @csrf
            <label>Файл XLSX <input type="file" name="file" accept=".xlsx" required></label>
            <button class="rounded-xl bg-indigo-600 text-white px-5 py-2">Предварительная проверка</button>
        </form>
    </div>
    @isset($report)
        <div class="rounded-2xl bg-white border p-6 space-y-4">
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                @foreach(['read' => 'Прочитано строк', 'valid' => 'Корректных строк', 'exact' => 'Точных совпадений', 'new_definitions' => 'Новых определений', 'new_links' => 'Новых привязок'] as $key => $label)
                    <div><dt class="text-gray-500">{{ $label }}</dt><dd class="font-semibold">{{ $report[$key] }}</dd></div>
                @endforeach
                @foreach(['CONFLICT' => 'Конфликтов', 'UNSUPPORTED' => 'Неподдерживаемых строк', 'INVALID' => 'Ошибочных строк'] as $key => $label)
                    <div><dt class="text-gray-500">{{ $label }}</dt><dd class="font-semibold">{{ $report['counts'][$key] }}</dd></div>
                @endforeach
            </dl>
            @if($report['blocked'])<p role="alert" class="text-red-700">Весь импорт заблокирован. Исправьте перечисленные ниже строки и выполните проверку заново.</p>@endif
            <label>Статус <select x-model="status" class="rounded-lg border-gray-300"><option value="">Все</option>@foreach($report['counts'] as $key => $count)<option value="{{ $key }}">{{ $key }} ({{ $count }})</option>@endforeach</select></label>
            <div class="overflow-x-auto max-h-[36rem]">
                <table class="w-full text-sm text-left"><thead><tr class="border-b"><th class="p-2">Строка</th><th class="p-2">Категория / группа</th><th class="p-2">Характеристика</th><th class="p-2">Определение</th><th class="p-2">Статус / причина</th></tr></thead><tbody>
                @foreach($report['rows'] as $row)
                    <tr class="border-b align-top" x-show="!status || status === '{{ $row['status'] }}'">
                        <td class="p-2">{{ $row['line'] }}</td>
                        <td class="p-2">{{ $row['raw']['category_slug'] }}<div class="text-gray-500">{{ $row['raw']['group_name'] }}</div></td>
                        <td class="p-2">{{ $row['raw']['attribute_name'] }}</td>
                        <td class="p-2 break-all">{{ $row['raw']['type'] }}; {{ $row['raw']['unit'] }}<br>Фильтр: {{ $row['raw']['is_filterable'] }}; порядок: {{ $row['raw']['sort_order'] }}<br>{{ $row['raw']['options'] }}</td>
                        <td class="p-2"><strong>{{ $row['status'] }}</strong>@foreach($row['errors'] as $error)<p class="text-red-700">{{ $error }}</p>@endforeach</td>
                    </tr>
                @endforeach
                </tbody></table>
            </div>
            @if(!empty($token) && !$report['blocked'])
                <form method="POST" action="{{ route('admin.attribute-import.apply') }}" enctype="multipart/form-data" class="space-y-3 border-t pt-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <p class="text-sm text-gray-600">Повторно выберите тот же файл. Подтверждение действительно 30 минут. При изменении файла или каталога потребуется новый предпросмотр.</p>
                    <label class="block">Проверенный XLSX <input type="file" name="file" accept=".xlsx" required></label>
                    <label class="block"><input type="checkbox" name="confirmed" value="1" required> Подтверждаю применение показанного плана</label>
                    <button class="rounded-xl bg-indigo-600 text-white px-5 py-2">Применить импорт</button>
                </form>
            @endif
        </div>
    @endisset
</div>
@endsection
