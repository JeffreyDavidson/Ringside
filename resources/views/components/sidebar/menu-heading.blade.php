<x-menu.menu-item class="pt-2.25 pb-px">
    <x-menu.menu-heading {{ $attributes->merge(['class' => 'uppercase text-2sm font-medium text-gray-500 ps-[10px] pe-[10px]']) }}>
        {{ $slot }}
    </x-menu.menu-heading>
</x-menu.menu-item>
