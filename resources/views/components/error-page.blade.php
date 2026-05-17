@props([
    'code',
    'eyebrow',
    'title',
    'description',
    'tone' => 'indigo',
])

@php
    $toneClasses = [
        'indigo' => [
            'dot' => 'bg-indigo-500',
            'pill' => 'border-indigo-100 bg-indigo-50 text-indigo-600',
            'glowOne' => 'bg-indigo-300/35',
            'glowTwo' => 'bg-violet-300/30',
            'mesh' => 'from-indigo-100/70 via-transparent to-violet-100/70',
        ],
        'amber' => [
            'dot' => 'bg-amber-500',
            'pill' => 'border-amber-100 bg-amber-50 text-amber-700',
            'glowOne' => 'bg-amber-300/35',
            'glowTwo' => 'bg-orange-300/30',
            'mesh' => 'from-amber-100/70 via-transparent to-orange-100/70',
        ],
        'rose' => [
            'dot' => 'bg-rose-500',
            'pill' => 'border-rose-100 bg-rose-50 text-rose-700',
            'glowOne' => 'bg-rose-300/35',
            'glowTwo' => 'bg-orange-300/25',
            'mesh' => 'from-rose-100/70 via-transparent to-orange-100/70',
        ],
    ][$tone];
@endphp

<div class="relative isolate min-h-screen overflow-hidden bg-slate-50">
    <div class="pointer-events-none absolute left-[-10rem] top-[-8rem] h-80 w-80 rounded-full {{ $toneClasses['glowOne'] }} blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-[-10rem] right-[-8rem] h-96 w-96 rounded-full {{ $toneClasses['glowTwo'] }} blur-3xl"></div>
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br {{ $toneClasses['mesh'] }}"></div>
    <div class="pointer-events-none absolute inset-0 opacity-[0.05] [background-image:radial-gradient(circle_at_1px_1px,rgba(15,23,42,.8)_1px,transparent_0)] [background-size:22px_22px]"></div>

    <div class="relative mx-auto grid min-h-screen w-full max-w-7xl items-center gap-10 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(380px,520px)] lg:gap-16 lg:px-8">
        <section class="order-2 pb-4 text-center lg:order-1 lg:pb-0 lg:text-left">
            <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $toneClasses['pill'] }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $toneClasses['dot'] }}"></span>
                {{ $eyebrow }}
            </div>

            <div class="mt-5 flex items-baseline justify-center gap-3 lg:justify-start">
                <div class="text-sm font-bold uppercase tracking-[0.3em] text-slate-400">{{ $code }}</div>
                <div class="h-px w-14 bg-slate-300"></div>
            </div>

            <h1 class="mt-4 max-w-3xl text-3xl font-black tracking-tight text-slate-950 sm:text-4xl lg:text-6xl">
                {{ $title }}
            </h1>

            <p class="mx-auto mt-5 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base lg:mx-0 lg:text-lg">
                {{ $description }}
            </p>

            @isset($actions)
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center lg:justify-start">
                    {{ $actions }}
                </div>
            @endisset

            @isset($links)
                <div class="mt-8 max-w-2xl lg:max-w-none">
                    {{ $links }}
                </div>
            @endisset
        </section>

        <section class="order-1 flex items-center justify-center lg:order-2">
            {{ $art }}
        </section>
    </div>
</div>
