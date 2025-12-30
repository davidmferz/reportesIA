@props([
    'variant' => 'primary', // primary, secondary, danger
    'size' => 'md', // sm, md, lg
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-hando focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-150';

    $variantClasses = [
        'primary' => 'bg-hando-primary hover:bg-hando-primary-hover text-white focus:ring-hando-primary shadow-hando-sm',
        'secondary' => 'bg-hando-gray-200 dark:bg-hando-gray-700 hover:bg-hando-gray-300 dark:hover:bg-hando-gray-600 text-hando-text-light dark:text-hando-text-dark focus:ring-hando-gray-400',
        'danger' => 'bg-hando-danger hover:bg-hando-danger-hover text-white focus:ring-hando-danger shadow-hando-sm',
    ];

    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>
    {{ $slot }}
</button>
