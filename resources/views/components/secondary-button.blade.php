<button {{ $attributes->merge(['type' => 'button', 'class' => 'wv-ui-button inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-5 py-2.5 text-sm font-medium text-neutral-700 shadow-sm transition-colors duration-150 motion-reduce:transition-none hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700']) }}>
    {{ $slot }}
</button>
