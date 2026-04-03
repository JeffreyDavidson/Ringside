@props([
    'icon' => 'heroicon-m-squares-2x2',
])

<span {{ $attributes->merge(['class' => 'flex shrink-0']) }}>
    <x-dynamic-component :component="$icon" class="size-5" />
</span>
