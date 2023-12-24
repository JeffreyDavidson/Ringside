<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>View Event Details</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('events.index')" label="Events" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :label="$event->name" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-details-card>
        <x-card>
            <x-card.body>
                <x-card.detail-link collapsibleLink="kt_event_view_details" resource="event" :href="route('events.edit', $event)" />
                <x-separator />
                <x-card.detail-container id="kt_event_view_details">
                    <x-card.detail-row property="Name" value="{{ $event->name }}" />
                    <x-card.detail-row property="Date" value="{{ $event->date?->format('Y-m-j g:i A') ?? 'Unscheduled' }}" />
                    <x-card.detail-row property="Venue" value="{{ $event->venue->name ?? 'No Venue Chosen' }}" />
                    <x-card.detail-row property="Preview" value="{{ $event->preview ?? 'No preview added'}}" />
                </x-card.detail-container>

                @if ($event->isUnscheduled())
                    <x-notice class="mt-4" title="This event needs your attention!" description="This event does not have a date and needs to be scheduled." />
                @endif
            </x-card.body>
        </x-card>
    </x-details-card>

    @include('events.partials.matches', ['matches' => $event->matches])
</x-layouts.app>
