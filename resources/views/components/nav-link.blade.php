@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-2 pt-1 border-b-2 border-purple-700 text-sm font-semibold leading-5 text-purple-950 focus:outline-none focus:border-purple-800 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-2 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-purple-800 hover:border-purple-300 focus:outline-none focus:text-purple-900 focus:border-purple-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
