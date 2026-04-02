@props([
    'icon' => null,
    'badge' => null,
    'nested' => false,
])

@if($nested)
    {{-- Nested Menu Label (with bullet) --}}
    <div class="flex border border-transparent items-center grow gap-[14px] ps-[10px] pe-[10px] py-[5px]"
        tabindex="0"
    >
        {{-- Bullet --}}
        <span class="flex w-[6px] -start-[3px] relative before:absolute before:top-0 before:size-[6px] before:rounded-full before:-translate-y-1/2"></span>

        <span class="text-2sm font-normal text-foreground">
            {{ $slot }}
        </span>

        @if($badge)
            <span class="ms-auto">
                <x-badge size="sm">{{ $badge }}</x-badge>
            </span>
        @endif
    </div>
@else
    {{-- Top-Level Menu Label (with icon) --}}
    <div class="flex border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
        tabindex="0"
    >
        @if($icon)
            <span class="flex items-center text-gray-500 w-[20px]">
                <i class="ki-filled ki-{{ $icon }} text-lg"></i>
            </span>
        @endif

        <span class="text-sm font-medium text-foreground
                     group-data-[collapsed=true]:opacity-0 group-hover:opacity-100 transition-opacity duration-200">
            {{ $slot }}
        </span>

        @if($badge)
            <span class="ms-auto
                         group-data-[collapsed=true]:opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <x-badge>{{ $badge }}</x-badge>
            </span>
        @endif
    </div>
@endif
