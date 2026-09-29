<button {{ $attributes->merge(['type' => 'submit', 'class' => 'wv-ui-button inline-flex items-center justify-center gap-2 rounded-xl border border-danger-500/20 bg-danger-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 motion-reduce:transition-none hover:bg-danger-700']) }}>
    {{ $slot }}
</button>
