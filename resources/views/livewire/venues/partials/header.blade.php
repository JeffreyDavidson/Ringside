<x-table.header>
    <x-card.title>
        <x-search resource="Venues" />
    </x-card.title>

    <x-card.toolbar>
        <div class="d-flex justify-content-end" data-kt-venue-table-toolbar="base">
            <x-buttons.create route="{{ route('venues.create') }}" resource="Venue" />
        </div>
    </x-card.toolbar>
</x-table.header>
