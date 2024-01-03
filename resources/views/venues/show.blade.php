<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Venue Details</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('venues.index')" label="Venues" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :label="$venue->name" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-details-page>
        <x-details-card>
            <x-card>
                <x-card.body>
                    <x-card.detail-link
                        collapsibleLink="kt_venue_view_details"
                        resource="venue"
                        :href="route('venues.edit', $venue)"
                    />
                    <x-separator />
                    <x-card.detail-container id="kt_venue_view_details">
                        <x-card.detail-row>
                            <x-card.detail-property label="Name" />
                            <x-card.detail-value>{{ $venue->name }}</x-card.detail-value>
                        </x-card.detail-row>
                        <x-card.detail-row>
                            <x-card.detail-property label="Address" />
                            <x-card.detail-value>
                                {{ $venue->street_address }}
                                @php
                                    echo "<br />";
                                @endphp
                                {{ $venue->city }}, {{ $venue->state }} {{ $venue->zip }}
                            </x-card.detail-value>
                        </x-card.detail-row>
                    </x-card.detail-container>
                </x-card.body>
            </x-card>
        </x-details-card>

        <x-details-data>
            <livewire:venues.events-list :venue="$venue" />
        </x-details-data>
    </x-details-page>
</x-layouts.app>
