@props([
    'id' => null,
])

<div 
    {{ $attributes->merge([
        'id' => $id,
        'class' => 'text-xs text-gray-600'
    ]) }}>
    {{ $slot }}
</div>