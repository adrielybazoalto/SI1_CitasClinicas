@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-purple-700 text-start text-base font-semibold text-purple-900 bg-purple-50/80 focus:outline-none focus:text-purple-950 focus:bg-purple-100 focus:border-purple-800 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-purple-900 hover:bg-purple-50/40 hover:border-purple-300 focus:outline-none focus:text-purple-900 focus:bg-purple-50 focus:border-purple-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
