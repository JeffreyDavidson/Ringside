<x-layouts.table-header title="Venues" :subtitle="__('venues.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Events\Venue::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11"
                @click="$dispatch('openModal', { component: 'venues.modals.form-modal' })"
            >
                Add Venue
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>
