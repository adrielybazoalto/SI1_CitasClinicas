<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 focus:bg-purple-900 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-all duration-150 ease-in-out cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
