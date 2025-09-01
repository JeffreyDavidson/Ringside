@props([
    'id' => null,
])

<div 
    {{ $attributes->merge([
        'id' => $id,
        'class' => 'text-xs text-muted-foreground'
    ]) }}>
    {{ $slot }}
</div>