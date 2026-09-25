@props([
    'href' => '#',
    'icon' => null,
    'active' => false,
])

<a
    href="{{ $href }}"
    aria-label="{{ trim(strip_tags((string) $slot)) }}"
    data-tooltip="{{ trim(strip_tags((string) $slot)) }}"
    data-sidebar-tooltip
    @class([
        'group/menu relative flex min-h-11 items-center gap-3 px-3 text-sm text-ringside-muted transition-[background-color,color] duration-300 ease-out hover:bg-ringside-surface hover:text-ringside-ink focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ringside-ink',
        'bg-ringside-surface-hover text-ringside-ink' => $active,
    ])
    @if ($active) aria-current="page" @endif
>
    @if ($icon)
        <span
            data-test="sidebar-menu-icon"
            @class([
                'shrink-0 transition-transform duration-[var(--sidebar-transition-duration)] ease-[var(--sidebar-transition-timing)] group-data-[collapsed=true]:translate-x-2.5 motion-reduce:transition-none',
                'text-ringside-signal' => $active,
            ])
        >{{ $icon }}</span>
    @endif
    <span
        data-test="sidebar-menu-label"
        class="max-w-40 truncate whitespace-nowrap transition-[max-width,opacity,transform] duration-[var(--sidebar-transition-duration)] ease-[var(--sidebar-transition-timing)] group-data-[collapsed=true]:max-w-0 group-data-[collapsed=true]:translate-x-2.5 group-data-[collapsed=true]:opacity-0 motion-reduce:transition-none"
    >{{ $slot }}</span>
    @if ($active)
        <span class="bg-ringside-signal absolute inset-y-3 start-0 w-px" aria-hidden="true"></span>
    @endif
</a>
