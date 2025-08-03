<x-layouts.table-header title="Managers">
    <x-slot:actions>
        @can('create', \App\Models\Manager::class)
            <x-buttons.primary size="sm" @click="$dispatch('openModal', { component: 'managers.modals.form-modal' })">Add
                Manager</x-buttons.primary>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data enum="\App\Enums\Shared\EmploymentStatus" />
