@props([])

<div x-data class="relative pt-2 pb-px">
    {{-- Text label - uses visibility to maintain layout, hidden when collapsed+not hovered --}}
    <span
        :class="$store.sidebar && ! $store.sidebar.expanded && ! $store.sidebar.hovered ? 'invisible' : 'visible'"
        class="text-muted-foreground ps-[10px] pe-[10px] text-xs font-medium uppercase transition-opacity duration-200"
    >
        {{ $slot }}
    </span>

    {{-- Ellipsis indicator - shown only when collapsed AND not hovering --}}
    <span
        :class="$store.sidebar && ! $store.sidebar.expanded && ! $store.sidebar.hovered ? 'visible' : 'invisible'"
        class="text-muted-foreground absolute start-0 bottom-1/2 ms-[0.225rem] translate-x-full tracking-[0.15em]"
    >
        ...
    </span>
</div>
