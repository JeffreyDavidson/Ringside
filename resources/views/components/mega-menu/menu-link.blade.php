@props([
    'isActive' => false,
])

<x-menu.menu-link
    {{ $attributes->merge(['class' => 'text-sm group-hover:text-primary group'])->class([
        'text-gray-900 font-medium' => $isActive,
        'text-gray-800' => !$isActive,
    ]) }}>
    <x-menu.menu-title class="text-nowrap">{{ $slot }}</x-menu.menu-title>
    <x-menu.menu-arrow class="flex lg:hidden" />
</x-menu.menu-link>
