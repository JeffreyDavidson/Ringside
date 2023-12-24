<x-table.header>
    <x-card.title>
        <x-search resource="Events" />
    </x-card.title>

    <x-card.toolbar>
        <div class="d-flex justify-content-end">
            <x-buttons.create route="{{ route('events.create') }}" resource="Event" />
        </div>
    </x-card.toolbar>
</x-table.header>
