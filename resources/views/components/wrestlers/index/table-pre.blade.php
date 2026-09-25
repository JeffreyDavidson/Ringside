<x-layouts.table-header title="Wrestlers" :subtitle="__('wrestlers.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Roster\Wrestlers\Wrestler::class)
            <x-button variant="ringside" size="md" class="min-h-11" tag="a" href="{{ route('wrestlers.create') }}">
                Add Wrestler
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data />
