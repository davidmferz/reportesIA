@props([
    'title' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-hando-card-dark rounded-hando shadow-hando border border-hando-border-light dark:border-hando-border-dark transition-colors duration-200']) }}>
    @if($title)
        <div class="px-6 py-4 border-b border-hando-border-light dark:border-hando-border-dark">
            <h3 class="text-lg font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $title }}</h3>
        </div>
    @endif

    <div class="{{ $padding ? 'p-6' : '' }}">
        {{ $slot }}
    </div>
</div>
