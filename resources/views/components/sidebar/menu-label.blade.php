@aware([
    'hasSub' => false,
])

<x-menu.menu-label class="border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]">
    {{ $slot }}
    @if ($hasSub)
        <x-menu.menu-arrow isOpen/>
    @endif
</x-menu.menu-label>
