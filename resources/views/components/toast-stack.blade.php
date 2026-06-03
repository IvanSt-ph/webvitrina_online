@php
    $messages = collect([
        ['type' => 'success', 'text' => session('success')],
        ['type' => 'error', 'text' => session('error')],
        ['type' => 'error', 'text' => $errors->any() ? $errors->first() : null],
    ])->filter(fn ($message) => filled($message['text']))->values();
@endphp

@if($messages->isNotEmpty())
    <div class="fixed right-3 top-3 z-[90] w-[calc(100%-1.5rem)] max-w-sm space-y-2 sm:right-5 sm:top-5">
        @foreach($messages as $message)
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 5200)"
                x-show="show"
                x-transition
                class="rounded-2xl border bg-white px-4 py-3 text-sm font-medium shadow-[0_14px_34px_rgba(15,23,42,0.10)] {{ $message['type'] === 'success' ? 'border-emerald-200 text-emerald-800' : 'border-rose-200 text-rose-800' }}"
            >
                <div class="flex items-start gap-3">
                    <i class="{{ $message['type'] === 'success' ? 'ri-checkbox-circle-line text-emerald-500' : 'ri-error-warning-line text-rose-500' }} mt-0.5 text-lg"></i>
                    <div class="min-w-0 flex-1">{{ $message['text'] }}</div>
                    <button type="button" @click="show = false" class="text-slate-400 hover:text-slate-600">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
