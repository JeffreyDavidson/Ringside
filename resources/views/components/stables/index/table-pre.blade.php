<x-layouts.table-header title="Stables">
    <x-slot:actions>
        @can('create', \App\Models\Stable::class)
            <x-buttons.primary size="sm" @click="$dispatch('openModal', { component: 'stables.modals.form-modal' })">Add
                Stable</x-buttons.primary>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data enum="\App\Enums\Shared\ActivationStatus" />
