@props([
    'title',
    'open' => false,
])

<div x-data="{ open: @json($open) }">
    {{-- Sub-Accordion Trigger (with bullet, not icon) --}}
    <div @click="open = !open"
        tabindex="0"
        @keydown.enter="open = !open"
        @keydown.space.prevent="open = !open"
        class="flex border border-transparent grow cursor-pointer gap-[14px] ps-[10px] pe-[10px] py-[5px]"
    >
        {{-- Bullet --}}
        <span class="flex w-[6px] -start-[3px] relative before:absolute before:top-0 before:size-[6px] before:rounded-full before:-translate-y-1/2"
              :class="open ? 'before:bg-primary' : 'hover:before:bg-primary'"></span>

        <span class="text-2sm font-normal me-1 text-foreground hover:text-primary"
              :class="open ? 'text-primary font-medium' : ''">
            {{ $title }}
        </span>

        {{-- Expand/Collapse Arrow --}}
        <span class="flex text-muted-foreground w-[20px] shrink-0 justify-end ms-auto me-[-10px]">
            <x-heroicon-s-chevron-down
                class="size-3 transition-transform duration-200"
                x-bind:class="open ? 'rotate-180' : ''"
            />
        </span>
    </div>

    {{-- Sub-Accordion Content --}}
    <div x-show="open"
        x-collapse
        class="relative before:absolute before:start-[32px] ps-[22px] before:top-0 before:bottom-0 before:border-s before:border-border"
    >
        {{ $slot }}
    </div>
</div>
