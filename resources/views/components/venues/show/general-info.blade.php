@props(['venue'])

<x-card.general-info>
    <x-card.general-info.stat :label="__('core.general_info.address')" :value="$venue->address->formatted()" />
</x-card.general-info>
