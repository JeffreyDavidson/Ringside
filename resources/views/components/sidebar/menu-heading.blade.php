@props([])

<div x-data
    class="pt-2 pb-px relative"
>
    {{-- Text label - uses visibility to maintain layout, hidden when collapsed+not hovered --}}
    <span :class="$store.sidebar && !$store.sidebar.expanded && !$store.sidebar.hovered ? 'invisible' : 'visible'"
          class="uppercase text-xs font-medium text-muted-foreground ps-[10px] pe-[10px] transition-opacity duration-200">
        {{ $slot }}
    </span>

    {{-- Ellipsis indicator - shown only when collapsed AND not hovering --}}
    <span :class="$store.sidebar && !$store.sidebar.expanded && !$store.sidebar.hovered ? 'visible' : 'invisible'"
          class="absolute bottom-1/2 start-0 ms-[0.225rem] translate-x-full tracking-[0.15em] text-muted-foreground">
        ...
    </span>
</div>
