@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-purple-200/80 focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 rounded-xl shadow-sm text-sm text-gray-800 placeholder:text-gray-400 transition-colors duration-150']) }}>
