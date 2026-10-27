<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-white border border-purple-200 rounded-xl font-semibold text-xs text-purple-900 uppercase tracking-wider shadow-sm hover:bg-purple-50/70 hover:border-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-30 transition-all duration-150 ease-in-out cursor-pointer']) }}>
    {{ $slot }}
</button>
