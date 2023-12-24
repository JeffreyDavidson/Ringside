<x-table.header>
    <x-card.title>
        <x-search resource="Referees" />
    </x-card.title>

    <x-card.toolbar>
        <div class="d-flex justify-content-end" data-kt-venue-table-toolbar="base">
            <x-buttons.create route="{{ route('referees.create') }}" resource="Referee" />
        </div>
    </x-card.toolbar>
</x-table.header>
