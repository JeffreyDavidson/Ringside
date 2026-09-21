@props([
    'for' => null,
    'required' => false,
    'badge' => null,
])

<label {{
    $attributes->merge([
        'for' => $for,
        'class' => 'flex gap-2 items-center w-full text-sm leading-none font-medium text-foreground',
    ])
}}>
    {{ $slot }}

    @if ($badge)
        <span class="bg-muted text-muted-foreground ml-1.5 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium">
            {{ $badge }}
        </span>
    @endif

    @if ($required)
        <span class="text-destructive ml-1">*</span>
    @endif
</label>
