<button {{ $attributes->merge(['type' => 'submit', 'class' => 'relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl border border-indigo-400/30 bg-indigo-500/90 px-5 py-2.5 text-sm font-semibold text-white shadow-lg backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-600 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
