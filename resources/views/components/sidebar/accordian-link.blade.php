@props([
    'isCurrent' => false,
])

<div class="flex flex-col m-0 p-0 group/menuItem">
    <a class="flex m-0 p-0 border border-transparent items-center grow menu-item-active:bg-secondary-active group-hover/menuItem:active:rounded-lg hover:bg-secondary-active hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
        {{ $attributes }}>
        <span @class([
            'items-center shrink-0 flex w-[6px] -start-[3px] relative before:absolute before:top-0 before:size-[6px] before:rounded-full before:-translate-y-1/2',
            'before:bg-primary' => $isCurrent,
        ])></span>
        <span
            class="flex items-center grow text-2sm font-normal text-gray-800 group-hover/menuItem:text-primary">{{ $slot }}</span>
    </a>
</div>
