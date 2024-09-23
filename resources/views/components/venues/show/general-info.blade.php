@props(['venue'])

<x-card.general-info>
    <x-card.general-info.stat label="Street Address" :value="$venue->street_address" />
    <x-card.general-info.stat label="City" :value="$venue->city" />
    <x-card.general-info.stat label="State" :value="$venue->state" />
    <x-card.general-info.stat label="Zip Code" :value="$venue->zipcode" />
</x-card.general-info>
