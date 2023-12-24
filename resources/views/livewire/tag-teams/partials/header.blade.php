<x-table.header>
    <x-card.title>
        <x-search resource="Tag Teams" />
    </x-card.title>

    <x-card.toolbar>
        <div class="d-flex justify-content-end" data-kt-venue-table-toolbar="base">
            <x-buttons.create route="{{ route('tag-teams.create') }}" resource="Tag Team" />
        </div>
    </x-card.toolbar>
</x-table.header>
