@props([
    'for' => null,
    'required' => false,
    'badge' => null,
])

<label {{
    $attributes->merge([
        'for' => $for,
        'class' => 'flex gap-2 items-center w-full text-sm leading-none font-medium text-ringside-ink',
    ])
}}>
    {{ $slot }}

    @if ($badge)
        <span class="bg-ringside-surface-panel text-ringside-muted ml-1.5 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium">
            {{ $badge }}
        </span>
    @endif

    @if ($required)
        <span class="text-ringside-signal-soft ml-1" aria-hidden="true">*</span>
    @endif
</label>
