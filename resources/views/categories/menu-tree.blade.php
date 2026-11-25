            @if(($categories ?? collect())->isNotEmpty())
                <ul class="space-y-1.5">
                    @foreach($categories as $cat)
                        <x-category-item :category="$cat" />
                    @endforeach
                </ul>
            @else
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-indigo-600 shadow-sm">
                        <i class="ri-folder-warning-line text-xl"></i>
                    </div>
                    <h3 class="mt-3 text-sm font-bold text-slate-950">Категории пока не добавлены</h3>
                    <p class="mt-1 text-sm leading-5 text-slate-500">Когда появятся разделы каталога, они будут здесь.</p>
                </div>
            @endif
