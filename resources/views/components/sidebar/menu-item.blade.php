@props([
    'href' => '#',
    'icon' => null,
    'active' => false,
])

<a
    href="{{ $href }}"
    aria-label="{{ trim(strip_tags((string) $slot)) }}"
    title="{{ trim(strip_tags((string) $slot)) }}"
    @class([
        'group/menu relative flex min-h-11 items-center gap-3 overflow-hidden px-3 text-sm text-ringside-muted transition-[background-color,color,gap,padding] duration-300 ease-out hover:bg-ringside-surface hover:text-ringside-ink focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ringside-ink group-data-[collapsed=true]:justify-center group-data-[collapsed=true]:gap-0 group-data-[collapsed=true]:px-0',
        'bg-ringside-surface-hover text-ringside-ink' => $active,
    ])
    @if ($active) aria-current="page" @endif
>
    @if ($icon)
        <span @class(['shrink-0', 'text-ringside-signal' => $active])>{{ $icon }}</span>
    @endif
    <span class="max-w-40 truncate whitespace-nowrap transition-[max-width,opacity,transform] duration-300 ease-out group-data-[collapsed=true]:max-w-0 group-data-[collapsed=true]:-translate-x-1 group-data-[collapsed=true]:opacity-0">{{ $slot }}</span>
    @if ($active)
        <span class="bg-ringside-signal absolute inset-y-3 start-0 w-px" aria-hidden="true"></span>
    @endif
</a>
