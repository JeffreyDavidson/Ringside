<x-card.general-info>
    <x-card.general-info.stat label="Date" :value="$event->date?->format('Y-m-j g:i A') ?? 'Unscheduled'" />
    @if ($event->venue)
        <x-card.general-info.links label="Venue">
            <x-route-link :route="route('venues.show', $event->venue)" label="{{ $event->venue->name }}" />
        </x-card.general-info.links>
    @else
        <x-card.general-info.stat label="Venue" value="No Venue Chosen" />
    @endif
    <x-card.general-info.stat label="Preview" :value="$event->preview ?? 'No Preview Added'" />
</x-card.general-info>
