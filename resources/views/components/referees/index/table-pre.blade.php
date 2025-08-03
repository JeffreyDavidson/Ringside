<x-layouts.table-header title="Referees">
    <x-slot:actions>
        @can('create', \App\Models\Referee::class)
            <x-buttons.primary size="sm" @click="$dispatch('openModal', { component: 'referees.modals.form-modal' })">Add
                Referee</x-buttons.primary>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data enum="\App\Enums\Shared\EmploymentStatus" />
