@if($paginator->hasPages())
    <div class="mt-8 flex justify-center fade-in" data-load-more-controls>
        @if($paginator->hasMorePages())
            <button
                type="button"
                data-load-more-button
                data-next-url="{{ $paginator->nextPageUrl() }}"
                class="inline-flex h-11 min-w-[220px] items-center justify-center gap-2 rounded-xl border border-indigo-100 bg-indigo-600 px-5 text-sm font-bold text-white shadow-lg shadow-indigo-600/15 transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70"
            >
                <i class="ri-add-line text-lg"></i>
                <span data-load-more-label>Показать ещё</span>
            </button>
        @else
            <div class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-500">
                Все товары показаны
            </div>
        @endif

        <noscript>
            <div class="mt-4">
                {{ $paginator->withQueryString()->links() }}
            </div>
        </noscript>
    </div>
@endif

@once
    @push('scripts')
        <script>
            document.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-load-more-button]');

                if (!button) {
                    return;
                }

                const root = button.closest('[data-load-more-root]');
                const grid = root?.querySelector('[data-load-more-grid]');
                const controls = root?.querySelector('[data-load-more-controls]');
                const nextUrl = button.dataset.nextUrl;
                const label = button.querySelector('[data-load-more-label]');
                const originalLabel = label?.textContent || 'Показать ещё';

                if (!root || !grid || !controls || !nextUrl || button.disabled) {
                    return;
                }

                button.disabled = true;
                if (label) {
                    label.textContent = 'Загружаем...';
                }

                try {
                    const response = await fetch(nextUrl, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Bad response');
                    }

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nextRoot = doc.querySelector(`[data-load-more-root="${root.dataset.loadMoreRoot}"]`);
                    const nextGrid = nextRoot?.querySelector('[data-load-more-grid]');
                    const nextControls = nextRoot?.querySelector('[data-load-more-controls]');

                    if (!nextRoot || !nextGrid) {
                        window.location.href = nextUrl;
                        return;
                    }

                    nextGrid.querySelectorAll('[data-load-more-item]').forEach((item, index) => {
                        if (!item.style.getPropertyValue('--delay-index')) {
                            item.style.setProperty('--delay-index', index);
                        }

                        grid.appendChild(item);
                        window.Alpine?.initTree(item);

                        requestAnimationFrame(() => {
                            item.classList.add('visible');
                        });
                    });

                    if (nextControls) {
                        controls.replaceWith(nextControls);
                    } else {
                        controls.remove();
                    }

                    window.history.pushState({}, '', nextUrl);
                } catch (error) {
                    window.location.href = nextUrl;
                } finally {
                    button.disabled = false;
                    if (label) {
                        label.textContent = originalLabel;
                    }
                }
            });
        </script>
    @endpush
@endonce
