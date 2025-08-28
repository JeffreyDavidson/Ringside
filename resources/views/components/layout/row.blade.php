@props([
    'gap' => '4',
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap -mx-' . $gap . ' ' . $class]) }}>
    {{ $slot }}
</div>