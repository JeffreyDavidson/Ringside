@props([
    'title' => 'Menu',
    'width' => 'lg', // sm, md, lg, xl, full
    'active' => false,
])

@php
    $widthClasses = [
        'xs' => 'w-[220px]',
        'sm' => 'w-[400px]',
        'md' => 'w-[600px]',
        'md-lg' => 'w-[670px]',
        'lg' => 'w-[700px]',
        'lg-xl' => 'w-[900px]',
        'xl' => 'w-[1000px]',
        'full' => 'w-[1240px]',
    ];
    $widthClass = $widthClasses[$width] ?? $widthClasses['lg'];
@endphp

{{-- Mega Menu Dropdown --}}
<div
    x-data="{ open: false }"
    @mouseenter="open = true"
    @mouseleave="open = false"
    class="relative flex items-center"
>
    {{-- Trigger --}}
    <button
        type="button"
        @click="open = !open"
        class="text-sm font-medium cursor-pointer transition-colors flex items-center gap-1 py-2"
        :class="open ? 'text-primary' : '{{ $active ? 'text-primary' : 'text-foreground hover:text-primary' }}'"
    >
        <span class="text-nowrap">{{ $title }}</span>
        <span class="flex lg:hidden text-muted-foreground">
            <x-heroicon-m-plus class="size-3" x-show="!open" />
            <x-heroicon-m-minus class="size-3" x-show="open" x-cloak />
        </span>
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="{{ $widthClass }} absolute top-full left-1/2 -translate-x-1/2 mt-2 z-50 bg-background border border-border rounded-xl shadow-lg overflow-visible"
        x-cloak
    >
        {{ $slot }}

        @if(isset($footer))
            <div class="flex flex-wrap items-center lg:justify-between rounded-b-xl border-t border-border px-4 py-4 lg:px-7.5 lg:py-5 gap-2.5 bg-muted/50">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
