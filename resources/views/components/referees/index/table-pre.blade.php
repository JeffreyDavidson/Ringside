<x-layouts.table-header title="Referees" :subtitle="__('referees.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Roster\Referees\Referee::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11"
                @click="$dispatch('openModal', { component: 'referees.modals.form-modal' })"
            >
                Add Referee
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data />
