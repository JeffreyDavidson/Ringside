@props([
    'variant' => 'primary',
    'size' => 'md',
    'tag' => 'button',
])

@php
    /*
     * Ringside has three button roles (see DESIGN.md): primary for the main action,
     * secondary for every other action, and destructive for actions that remove or end
     * something. Older variant names are kept as aliases so existing views stay on brand.
     */
    $role = match ($variant) {
        'secondary', 'light', 'success', 'warning', 'info' => 'secondary',
        'destructive', 'danger' => 'destructive',
        default => 'primary',
    };

    $sizes = [
        'sm' => 'min-h-9 px-3 text-xs gap-1.5',
        'md' => 'min-h-11 px-4 text-sm gap-2',
        'xl' => 'min-h-14 px-6 py-3 text-base gap-2',
    ];

    $roles = [
        'primary' => 'border-primary bg-primary text-white hover:border-primary-hover hover:bg-primary-hover active:border-primary-pressed active:bg-primary-pressed',
        'secondary' => 'border-outline bg-transparent text-foreground hover:border-white hover:bg-surface-hover',
        'destructive' => 'border-signal-border bg-transparent text-signal-soft hover:border-primary-pressed hover:bg-primary-pressed hover:text-white',
    ];
@endphp

<{{ $tag }}
    {{
        $attributes->merge(['type' => $tag === 'button' ? 'button' : null])->class([
            'inline-flex cursor-pointer items-center justify-center rounded-none border font-bold transition-colors',
            'focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-foreground',
            'disabled:cursor-not-allowed disabled:opacity-50',
            $sizes[$size] ?? $sizes['md'],
            $roles[$role],
        ])
    }}
>
    {{ $slot }}
</{{ $tag }}>
