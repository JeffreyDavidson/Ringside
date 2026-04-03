@props([
    'href' => '#',
    'icon' => null,
    'iconRight' => null,
    'badge' => null,
    'active' => false,
])

{{-- Menu Item within Dropdown --}}
<div>
    <a
        href="{{ $href }}"
        {{ $attributes->merge([
            'class' => 'flex items-center gap-2.5 px-2.5 py-2 rounded-md transition-colors ' .
                ($active
                    ? 'text-primary bg-primary/5'
                    : 'text-foreground hover:text-primary hover:bg-accent')
        ]) }}
        tabindex="0"
    >
        @if($icon)
            <span class="w-5 flex items-center justify-center text-muted-foreground">
                <x-dynamic-component :component="$icon" class="size-4" />
            </span>
        @endif
        <span class="grow text-sm text-nowrap">
            {{ $slot }}
        </span>
        @if($iconRight)
            <span class="text-muted-foreground">
                <x-dynamic-component :component="$iconRight" class="size-4" />
            </span>
        @endif
        @if($badge)
            <span class="flex items-center ms-2.5">
                <x-badge size="sm">{{ $badge }}</x-badge>
            </span>
        @endif
    </a>
</div>
