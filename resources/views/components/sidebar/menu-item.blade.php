@props([
    'href' => '#',
    'icon' => null,
    'active' => false,
])

<a
    href="{{ $href }}"
    @class([
        'group/menu relative flex min-h-11 items-center gap-3 px-3 text-sm text-ringside-muted transition-colors hover:bg-ringside-surface hover:text-ringside-ink focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ringside-ink',
        'bg-ringside-surface-hover text-ringside-ink' => $active,
    ])
    @if ($active) aria-current="page" @endif
>
    @if ($icon)
        <span @class(['shrink-0', 'text-ringside-signal' => $active])>{{ $icon }}</span>
    @endif
    <span class="truncate group-data-[collapsed=true]:hidden">{{ $slot }}</span>
    @if ($active)
        <span class="bg-ringside-signal absolute inset-y-3 start-0 w-px" aria-hidden="true"></span>
    @endif
</a>
