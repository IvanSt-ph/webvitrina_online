@php
    $initialToasts = collect([
        ['type' => 'success', 'text' => session('success')],
        ['type' => 'error', 'text' => session('error')],
        ['type' => 'warning', 'text' => session('warning')],
        ['type' => 'info', 'text' => session('info')],
    ])->filter(fn (array $toast) => filled($toast['text']))
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
        },
        add(detail) {
            const type = ['success', 'error', 'warning', 'info'].includes(detail?.type) ? detail.type : 'info';
            const text = String(detail?.message ?? detail?.text ?? '').trim();
            if (!text) return;

            const toast = { id: `client-${++this.nextId}`, type, text };
            this.toasts.push(toast);
            this.$nextTick(() => this.schedule(toast));
        },
        schedule(toast) {
            const delay = ['error', 'warning'].includes(toast.type) ? 8000 : 5200;
            window.setTimeout(() => this.close(toast.id), delay);
        },
        close(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
        classes(type) {
            return {
                success: 'border-success-200 bg-success-50 text-success-950',
                error: 'border-danger-200 bg-danger-50 text-danger-950',
                warning: 'border-warning-200 bg-warning-50 text-warning-950',
                info: 'border-info-200 bg-info-50 text-info-950',
            }[type];
        },
        icon(type) {
            return {
                success: 'ri-checkbox-circle-line text-success-600',
                error: 'ri-error-warning-line text-danger-600',
                warning: 'ri-alert-line text-warning-600',
                info: 'ri-information-line text-info-600',
            }[type];
        },
    }"
    @wv-toast.window="add($event.detail)"
    @resize.window="syncBottomOffset()"
    :style="{ bottom: bottomOffset ? (bottomOffset + 16) + 'px' : 'calc(env(safe-area-inset-bottom) + 1.25rem)' }"
    class="pointer-events-none fixed inset-x-3 bottom-5 z-[90] flex flex-col gap-3 sm:inset-x-auto sm:right-5 sm:w-full sm:max-w-md"
    aria-live="polite"
    aria-atomic="false"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
            x-transition:enter-start="translate-y-3 opacity-0 motion-reduce:transform-none"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-3 opacity-0 motion-reduce:transform-none"
            :class="classes(toast.type)"
            :role="['error', 'warning'].includes(toast.type) ? 'alert' : 'status'"
            class="pointer-events-auto w-full rounded-2xl border px-4 py-4 text-[15px] font-semibold leading-6 shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-white/70 sm:px-5"
        >
            <div class="flex items-start gap-3">
                <i :class="icon(toast.type)" class="mt-0.5 shrink-0 text-2xl" aria-hidden="true"></i>
                <p class="min-w-0 flex-1" x-text="toast.text"></p>
                <button
                    type="button"
                    @click="close(toast.id)"
                    class="-mr-1 -mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-neutral-500 transition hover:bg-white/70 hover:text-neutral-800 focus:outline-none focus:ring-2 focus:ring-current motion-reduce:transition-none"
                    aria-label="Закрыть уведомление"
                >
                    <i class="ri-close-line text-xl" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </template>
</div>
