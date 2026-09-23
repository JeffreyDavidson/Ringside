<x-layouts.table-header title="Stables" :subtitle="__('stables.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Roster\Stables\Stable::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11"
                @click="$dispatch('openModal', { component: 'stables.modals.form-modal' })"
            >
                Add Stable
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data />
