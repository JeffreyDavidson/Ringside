<x-layouts.table-header title="Managers">
    <x-slot:actions>
        @can('create', \App\Models\Roster\Managers\Manager::class)
            <x-buttons.primary size="sm" @click="$dispatch('openModal', { component: 'managers.modals.form-modal' })">
                Add Manager</x-buttons.primary>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data />
