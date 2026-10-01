@props([
    'id' => null,
])

<div {{
    $attributes->merge([
        'id' => $id,
        'class' => 'text-xs text-ringside-muted',
    ])
}}>
    {{ $slot }}
</div>
