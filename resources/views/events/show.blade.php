<x-layouts.app>
    <x-slot:toolbar>
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

    <x-details-page>
        <x-details-card>
            <x-card>
                <x-card.body>
                    <x-card.detail-link
                        collapsibleLink="kt_event_view_details"
                        resource="event"
                        :href="route('events.edit', $event)"
                    />
                    <x-separator />
                    <x-card.detail-container id="kt_event_view_details">
                        <x-card.detail-row>
                            <x-card.detail-property label="Name" />
                            <x-card.detail-value>{{ $event->name }}</x-card.detail-value>
                        </x-card.detail-row>
                        <x-card.detail-row>
                            <x-card.detail-property label="Date" />
                            <x-card.detail-value>
                                {{ $event->date?->format('Y-m-j g:i A') ?? 'Unscheduled' }}
                            </x-card.detail-value>
                        </x-card.detail-row>
                        <x-card.detail-row>
                            <x-card.detail-property label="Venue" />
                            <x-card.detail-value>
                                @if ($event->venue)
                                    <x-route-link
                                        :route="route('venues.show', $event->venue)"
                                        label="{{ $event->venue->name }}"
                                    />
                                @else
                                    {{ "No Venue Chosen" }}
                                @endif
                            </x-card.detail-value>
                        </x-card.detail-row>
                        <x-card.detail-row>
                            <x-card.detail-property label="Preview" />
                            <x-card.detail-value>{{ $event->preview ?? "No Preview Added" }}</x-card.detail-value>
                        </x-card.detail-row>
                    </x-card.detail-container>

                    @if ($event->isUnscheduled())
                        <x-notice
                            class="mt-4"
                            title="This event needs your attention!"
                            description="This event does not have a date and needs to be scheduled."
                        />
                    @endif
                </x-card.body>
            </x-card>
        </x-details-card>

        <x-details-data>
            @if ($event->matches->isNotEmpty())
                <x-events.matches-list :matches="$event->matches" />
            @endif
        </x-details-data>
    </x-details-page>
</x-layouts.app>
