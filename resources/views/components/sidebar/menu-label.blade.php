@props([
    'icon' => null,
    'badge' => null,
    'nested' => false,
])

@if ($nested)
    {{-- Nested Menu Label (with bullet) --}}
    <div class="flex grow items-center gap-[14px] border border-transparent py-[5px] ps-[10px] pe-[10px]" tabindex="0">
        {{-- Bullet --}}
        <span class="relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full"></span>

        <span class="text-2sm text-foreground font-normal"> {{ $slot }} </span>

        @if ($badge)
            <span class="ms-auto">
                <x-badge size="sm">{{ $badge }}</x-badge>
            </span>
        @endif
    </div>
@else
    {{-- Top-Level Menu Label (with icon) --}}
    <div class="flex gap-[10px] border border-transparent py-[6px] ps-[10px] pe-[10px]" tabindex="0">
        @if ($icon)
            <span class="flex w-[20px] shrink-0 items-center text-gray-500"> {{ $icon }} </span>
        @endif

        <span class="text-foreground text-sm font-medium transition-opacity duration-200 group-hover:opacity-100 group-data-[collapsed=true]:opacity-0">
            {{ $slot }}
        </span>

        @if ($badge)
            <span class="ms-auto transition-opacity duration-200 group-hover:opacity-100 group-data-[collapsed=true]:opacity-0">
                <x-badge>{{ $badge }}</x-badge>
            </span>
        @endif
    </div>
@endif
