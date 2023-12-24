<x-table.header>
    <x-card.title>
        <x-search resource="Titles" />
    </x-card.title>

    <x-card.toolbar>
        <div class="d-flex justify-content-end">
            <x-buttons.create route="{{ route('titles.create') }}" resource="Title" />
        </div>
    </x-card.toolbar>
</x-table.header>
