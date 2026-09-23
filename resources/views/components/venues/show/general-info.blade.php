@props(['venue'])

<x-card.general-info>
    <x-card.general-info.stat label="Address" :value="$venue->address->formatted()" />
</x-card.general-info>
