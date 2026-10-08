<x-card.general-info>
    <x-card.general-info.stat :label="__('core.general_info.status')" :value="$event->status->label()" />
    <x-card.general-info.stat
        :label="__('core.general_info.date')"
        :value="$event->local_date?->format('Y-m-d g:i A') ?? __('core.general_info.unscheduled')"
    />
    @if ($event->venue)
        <x-card.general-info.links :label="__('core.general_info.venue')">
            <x-route-link :route="route('venues.show', $event->venue)" :label="$event->venue->name" />
        </x-card.general-info.links>
    @else
        <x-card.general-info.stat
            :label="__('core.general_info.venue')"
            :value="__('core.general_info.no_venue_chosen')"
        />
    @endif
    <x-card.general-info.stat
        :label="__('core.general_info.preview')"
        :value="$event->preview ?? __('core.general_info.no_preview_added')"
    />
</x-card.general-info>
