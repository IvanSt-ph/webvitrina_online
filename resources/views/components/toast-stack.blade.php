@props(['showFlash' => true])

@php
    $initialToasts = collect($showFlash ? [
        ['type' => 'success', 'text' => session('success')],
        ['type' => 'error', 'text' => session('error')],
        ['type' => 'warning', 'text' => session('warning')],
        ['type' => 'info', 'text' => session('info')],
    ] : [])->filter(fn (array $toast) => filled($toast['text']))
        ->unique(fn (array $toast) => $toast['type'] . '|' . $toast['text'])
        ->values()
        ->map(fn (array $toast, int $index) => [
            ...$toast,
            'id' => 'flash-' . $index,
        ]);
@endphp

<div
    data-toast-stack
    x-data="{
        toasts: @js($initialToasts),
        nextId: 0,
        bottomOffset: 0,
        navObserver: null,
        timers: {},
        syncBottomOffset() {
            this.bottomOffset = Math.max(0, ...Array.from(document.querySelectorAll('[data-toast-bottom-nav]')).map((nav) => {
                const rect = nav.getBoundingClientRect();
                return rect.width && rect.height ? Math.max(0, window.innerHeight - rect.top) : 0;
            }));
        },
        init() {
            this.$nextTick(() => {
                this.toasts.forEach((toast) => this.schedule(toast));
                this.syncBottomOffset();
                this.navObserver = new ResizeObserver(() => this.syncBottomOffset());
                document.querySelectorAll('[data-toast-bottom-nav]').forEach((nav) => this.navObserver.observe(nav));
            });
        },
        destroy() {
            this.navObserver?.disconnect();
            Object.values(this.timers).forEach((timer) => window.clearTimeout(timer));
        },
        add(detail) {
            const type = ['success', 'error', 'warning', 'info'].includes(detail?.type) ? detail.type : 'info';
            const text = String(detail?.message ?? detail?.text ?? '').trim();
            if (!text) return;
            if (this.toasts.some((toast) => toast.type === type && toast.text === text)) return;

            const toast = { id: `client-${++this.nextId}`, type, text };
            this.toasts.push(toast);
            this.$nextTick(() => this.schedule(toast));
        },
        schedule(toast) {
            const delay = ['error', 'warning'].includes(toast.type) ? 9000 : 4000;
            this.timers[toast.id] = window.setTimeout(() => this.close(toast.id), delay);
        },
        close(id) {
            window.clearTimeout(this.timers[id]);
            delete this.timers[id];
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
        icon(type) {
            return {
                success: 'ri-check-line bg-emerald-50 text-emerald-700',
                error: 'ri-close-line bg-rose-50 text-rose-700',
                warning: 'ri-alert-line bg-amber-50 text-amber-700',
                info: 'ri-information-line bg-sky-50 text-sky-700',
            }[type];
        },
    }"
    @wv-toast.window="add($event.detail)"
    @resize.window="syncBottomOffset()"
    :style="{ bottom: bottomOffset ? (bottomOffset + 16) + 'px' : 'calc(env(safe-area-inset-bottom) + 1.25rem)' }"
    class="pointer-events-none fixed inset-x-3 bottom-5 z-[90] flex flex-col items-center gap-2 sm:left-1/2 sm:right-auto sm:w-[min(25rem,calc(100vw-2rem))] sm:-translate-x-1/2"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
            x-transition:enter-start="translate-y-3 opacity-0 motion-reduce:transform-none"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-3 opacity-0 motion-reduce:transform-none"
            :role="['error', 'warning'].includes(toast.type) ? 'alert' : 'status'"
            :aria-live="['error', 'warning'].includes(toast.type) ? 'assertive' : 'polite'"
            aria-atomic="true"
            class="pointer-events-auto w-fit max-w-full rounded-[17px] border border-white/85 bg-white/95 px-3.5 py-2.5 text-sm font-medium leading-5 text-slate-900 shadow-[0_10px_30px_rgba(15,23,42,0.15)] supports-[backdrop-filter:blur(1px)]:bg-white/80 supports-[backdrop-filter:blur(1px)]:backdrop-blur-xl"
        >
            <div class="flex items-center gap-2.5">
                <i :class="icon(toast.type)" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-base" aria-hidden="true"></i>
                <p class="min-w-0 break-words" x-text="toast.text"></p>
                <button
                    type="button"
                    @click="close(toast.id)"
                    class="-mr-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 motion-reduce:transition-none"
                    aria-label="Закрыть уведомление"
                >
                    <span class="text-xl leading-none" aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    </template>
</div>
