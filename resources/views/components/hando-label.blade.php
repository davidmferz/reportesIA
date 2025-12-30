@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-hando-text-light dark:text-hando-text-dark mb-2']) }}>
    {{ $value ?? $slot }}
    @if($required)
        <span class="text-hando-danger ml-1">*</span>
    @endif
</label>
