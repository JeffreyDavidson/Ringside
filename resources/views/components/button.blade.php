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
        'primary' => 'border-ringside-red bg-ringside-red text-ringside-white hover:border-ringside-red-dark hover:bg-ringside-red-dark',
        'secondary' => 'border-ringside-outline bg-transparent text-ringside-ink hover:border-ringside-white hover:bg-ringside-surface-hover',
        'destructive' => 'border-ringside-signal-border bg-transparent text-ringside-signal-soft hover:border-ringside-red-dark hover:bg-ringside-red-dark hover:text-ringside-white',
    ];
@endphp

<{{ $tag }}
    {{
        $attributes->merge(['type' => $tag === 'button' ? 'button' : null])->class([
            'inline-flex cursor-pointer items-center justify-center rounded-none border font-bold transition-colors',
            'focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ringside-white',
            'disabled:cursor-not-allowed disabled:opacity-50',
            $sizes[$size] ?? $sizes['md'],
            $roles[$role],
        ])
    }}
>
    {{ $slot }}
</{{ $tag }}>
