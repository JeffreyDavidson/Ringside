<x-layouts.table-header title="Venues">
    <x-slot:actions>
        @can('create', \App\Models\Venue::class)
            <x-buttons.primary size="sm" @click="$dispatch('openModal', { component: 'venues.modals.form-modal' })">Add
                Venue</x-buttons.primary>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data />
