@props([
    'disabled' => false,
    'icon' => null,
    'type' => 'text',
])

<div class="relative">
    @if($icon)
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            {!! $icon !!}
        </div>
    @endif

    <input
        type="{{ $type }}"
        @disabled($disabled)
        {{ $attributes->merge([
            'class' => 'block w-full rounded-hando border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-gray-800 text-hando-text-light dark:text-hando-text-dark placeholder-hando-gray-400 focus:outline-none focus:ring-2 focus:ring-hando-primary focus:border-transparent transition-colors duration-200 text-sm ' . ($icon ? 'pl-10' : 'px-3') . ' py-2.5'
        ]) }}
    >
</div>
