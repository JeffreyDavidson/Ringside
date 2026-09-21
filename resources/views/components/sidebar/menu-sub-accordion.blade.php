@props([
    'title',
    'open' => false,
])

<div x-data="{ open: @json($open) }">
    {{-- Sub-Accordion Trigger (with bullet, not icon) --}}
    <div
        @click="open = ! open"
        tabindex="0"
        @keydown.enter="open = ! open"
        @keydown.space.prevent="open = ! open"
        class="flex grow cursor-pointer gap-[14px] border border-transparent py-[5px] ps-[10px] pe-[10px]"
    >
        {{-- Bullet --}}
        <span
            class="relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full"
            :class="open ? 'before:bg-primary' : 'hover:before:bg-primary'"
        ></span>

        <span
            class="text-2sm text-foreground hover:text-primary me-1 font-normal"
            :class="open ? 'text-primary font-medium' : ''"
        >
            {{ $title }}
        </span>

        {{-- Expand/Collapse Arrow --}}
        <span class="text-muted-foreground ms-auto me-[-10px] flex w-[20px] shrink-0 justify-end">
            <x-heroicon-s-chevron-down
                class="size-3 transition-transform duration-200"
                x-bind:class="open ? 'rotate-180' : ''"
            />
        </span>
    </div>

    {{-- Sub-Accordion Content --}}
    <div
        x-show="open"
        x-collapse
        class="before:border-border relative ps-[22px] before:absolute before:start-[32px] before:top-0 before:bottom-0 before:border-s"
    >
        {{ $slot }}
    </div>
</div>
