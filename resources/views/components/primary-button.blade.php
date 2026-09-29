<button {{ $attributes->merge(['type' => 'submit', 'class' => 'wv-ui-button relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl border border-brand-400/30 bg-brand-500/90 px-5 py-2.5 text-sm font-semibold text-white shadow-sm backdrop-blur-sm transition-colors duration-150 motion-reduce:transition-none hover:bg-brand-600']) }}>
    {{ $slot }}
</button>
