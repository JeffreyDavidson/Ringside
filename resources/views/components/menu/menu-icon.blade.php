@props([
    'icon' => 'ki-element-11',
])

<span {{ $attributes->merge(['class' => 'flex shrink-0']) }}>
    <i class="{{ $icon }} text-lg"></i>
</span>
