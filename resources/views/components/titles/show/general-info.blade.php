@props(['title'])

<x-card.general-info>
    <x-card.general-info.links label="Current Champion">
        @if ($title->currentChampionship)
            {{ $title->currentChampionship->currentChampion->name }}
        @else
            {{ 'Vacant' }}
        @endif
    </x-card.general-info.links>
    <x-card.general-info.stat label="Date Introduced" :value="$title->activatedAt?->toDateString() ?? 'No Start Date Set'" />
</x-card.general-info>
