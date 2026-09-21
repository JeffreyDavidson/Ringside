@props([
    'title',
    'icon' => null,
    'open' => false,
])

<div x-data="{ open: @json($open) }">
    {{-- Accordion Trigger --}}
    <div
        @click="open = ! open"
        tabindex="0"
        @keydown.enter="open = ! open"
        @keydown.space.prevent="open = ! open"
        class="flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] ps-[10px] pe-[10px]"
    >
        @if ($icon)
            <span class="flex w-[20px] shrink-0 items-center text-gray-500"> {{ $icon }} </span>
        @endif

        <span class="text-foreground hover:text-primary text-sm font-medium transition-opacity duration-200 group-hover:opacity-100 group-data-[collapsed=true]:opacity-0">
            {{ $title }}
        </span>

        <span class="text-muted-foreground ms-auto me-[-10px] flex w-[20px] shrink-0 justify-end transition-opacity duration-200 group-hover:opacity-100 group-data-[collapsed=true]:opacity-0">
            <x-heroicon-s-chevron-down
                class="size-3 transition-transform duration-200"
                x-bind:class="open ? 'rotate-180' : ''"
            />
        </span>
    </div>

    {{-- Accordion Content --}}
    <div
        x-show="open"
        x-collapse
        class="before:border-border relative ps-[10px] before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s"
    >
        {{ $slot }}
    </div>
</div>
