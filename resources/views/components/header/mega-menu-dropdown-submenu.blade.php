@props([
    'icon' => null,
])

<div
    x-data="{ open: false }"
    @mouseenter="open = true"
    @mouseleave="open = false"
    class="relative"
>
    {{-- Trigger --}}
    <div
        class="flex items-center gap-2.5 px-2.5 py-2 rounded-md transition-colors cursor-pointer text-foreground hover:text-primary hover:bg-accent"
    >
        @if($icon)
            <span class="w-5 flex items-center justify-center text-muted-foreground">
                <x-dynamic-component :component="$icon" class="size-4" />
            </span>
        @endif
        <span class="grow text-sm text-nowrap">
            {{ $title }}
        </span>
        <span class="text-muted-foreground">
            <x-heroicon-m-chevron-right class="size-3" />
        </span>
    </div>

    {{-- Submenu --}}
    <div
        x-show="open"
        x-transition
        class="absolute left-full top-0 ml-1 w-[220px] bg-background border border-border rounded-xl shadow-lg py-2.5 z-50"
        x-cloak
    >
        {{ $slot }}
    </div>
</div>
