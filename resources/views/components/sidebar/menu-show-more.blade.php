@props([
    'count' => 0,
    'deep' => false,
])

<div x-data="{ open: false }" class="flex flex-col-reverse">
    {{-- Toggle Trigger --}}
    <div @click="open = !open"
        tabindex="0"
        @keydown.enter="open = !open"
        @keydown.space.prevent="open = !open"
        @class([
            'flex border border-transparent grow cursor-pointer ps-[10px] pe-[10px] py-[5px]',
            'gap-[5px]' => $deep,
            'gap-[14px]' => !$deep,
        ])
    >
        {{-- Bullet --}}
        <span class="flex w-[6px] -start-[3px] relative before:absolute before:top-0 before:size-[6px] before:rounded-full before:-translate-y-1/2 hover:before:bg-primary"></span>

        <span class="text-2sm font-normal text-secondary-foreground">
            <span x-show="open" x-cloak>Show less</span>
            <span x-show="!open">Show {{ $count }} more</span>
        </span>

        {{-- Expand/Collapse Arrow --}}
        <span class="flex text-muted-foreground w-[20px] shrink-0 justify-end ms-auto me-[-10px]">
            <span class="inline-flex" x-show="!open">
                <i class="ki-filled ki-plus text-[11px]"></i>
            </span>
            <span class="inline-flex" x-show="open" x-cloak>
                <i class="ki-filled ki-minus text-[11px]"></i>
            </span>
        </span>
    </div>

    {{-- Hidden Content (appears above trigger due to flex-col-reverse) --}}
    <div x-show="open" x-collapse class="gap-1">
        {{ $slot }}
    </div>
</div>
