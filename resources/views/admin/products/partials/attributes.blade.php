@if(isset($attributes) && $attributes->count())
    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="flex items-center gap-2 font-bold text-slate-950">
                    <i class="ri-equalizer-line text-indigo-600"></i>
                    Характеристики товара
                </div>
                <p class="mt-1 text-sm text-slate-500">Эти поля зависят от выбранной категории и участвуют в фильтрах витрины.</p>
            </div>
            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-500 ring-1 ring-slate-200">
                {{ $attributes->count() }} полей
            </span>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @foreach($attributes as $attr)
                @php
                    $val = old('attributes.'.$attr->id, $attr->value ?? null);
                    $fieldClass = 'h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100';
                @endphp

                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-800">{{ $attr->name }}</span>

                    @if($attr->type === 'select')
                        <select name="attributes[{{ $attr->id }}]" class="{{ $fieldClass }}">
                            <option value="">Не выбрано</option>
                            @foreach((array) $attr->options as $option)
                                <option value="{{ $option }}" @selected((string) $val === (string) $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @elseif($attr->type === 'number')
                        <input type="number" name="attributes[{{ $attr->id }}]" value="{{ $val }}" class="{{ $fieldClass }}">
                    @elseif($attr->type === 'boolean')
                        <input type="hidden" name="attributes[{{ $attr->id }}]" value="0">
                        <span class="inline-flex h-11 items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
                            <input type="checkbox"
                                   name="attributes[{{ $attr->id }}]"
                                   value="1"
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   @checked((bool) $val)>
                            Да
                        </span>
                    @elseif($attr->type === 'color')
                        @if($attr->colors && $attr->colors->count())
                            <div class="flex flex-wrap gap-2">
                                @foreach($attr->colors as $color)
                                    <label class="group cursor-pointer">
                                        <input type="radio"
                                               name="attributes[{{ $attr->id }}]"
                                               value="{{ $color->id }}"
                                               class="peer sr-only"
                                               @checked((string) $val === (string) $color->id)>
                                        <span class="flex h-10 min-w-10 items-center justify-center rounded-full border-2 border-slate-200 bg-white p-1 transition peer-checked:border-indigo-600 peer-checked:shadow-md peer-checked:shadow-indigo-900/10"
                                              title="{{ $color->name }}">
                                            <span class="h-7 w-7 rounded-full border border-black/10" style="background: {{ $color->hex }}"></span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @if($val && $attr->colors->firstWhere('id', (int) $val))
                                <p class="mt-2 text-xs text-slate-500">Выбран: {{ $attr->colors->firstWhere('id', (int) $val)->name }}</p>
                            @endif
                        @else
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                                Цвета для этой характеристики ещё не настроены.
                            </div>
                        @endif
                    @else
                        <input type="text" name="attributes[{{ $attr->id }}]" value="{{ $val }}" class="{{ $fieldClass }}">
                    @endif
                </label>
            @endforeach
        </div>
    </div>
@else
    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
        <div class="font-bold text-slate-700">Характеристики появятся после выбора категории</div>
        <p class="mt-1">Если у категории настроены цвет, марка, размер или другие свойства, админ сможет заполнить их здесь.</p>
    </div>
@endif
