<x-layouts.table-header title="Events">
    <x-slot:actions>
        @can('create', \App\Models\Events\Event::class)
            <x-button
                variant="ringside"
                size="md"
                @click="$dispatch('openModal', { component: 'events.modals.form-modal' })"
            >
                Add Event
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data />
