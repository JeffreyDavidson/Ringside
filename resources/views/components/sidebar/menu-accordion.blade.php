@props([
    'title',
    'icon' => null,
    'open' => false,
])

<div x-data="{ open: @json($open) }">
    {{-- Accordion Trigger --}}
    <div @click="open = !open"
        tabindex="0"
        @keydown.enter="open = !open"
        @keydown.space.prevent="open = !open"
        class="flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
    >
        @if($icon)
            <span class="flex items-center text-gray-500 w-[20px] shrink-0">
                {{ $icon }}
            </span>
        @endif

        <span class="text-sm font-medium text-foreground hover:text-primary
                     group-data-[collapsed=true]:opacity-0 group-hover:opacity-100 transition-opacity duration-200">
            {{ $title }}
        </span>

        <span class="flex text-muted-foreground w-[20px] shrink-0 justify-end ms-auto me-[-10px]
                     group-data-[collapsed=true]:opacity-0 group-hover:opacity-100 transition-opacity duration-200">
            <x-heroicon-s-chevron-down
                class="size-3 transition-transform duration-200"
                x-bind:class="open ? 'rotate-180' : ''"
            />
        </span>
    </div>

    {{-- Accordion Content --}}
    <div x-show="open"
        x-collapse
        class="ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border"
    >
        {{ $slot }}
    </div>
</div>
