<x-layouts.table-header title="Wrestlers">
    <x-slot:actions>
        @can('create', \App\Models\Wrestler::class)
            <x-buttons.primary size="sm"
                @click="$dispatch('openModal', { component: 'wrestlers.modals.form-modal' })">Add
                Wrestler</x-buttons.primary>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data enum="\App\Enums\Shared\EmploymentStatus" />
