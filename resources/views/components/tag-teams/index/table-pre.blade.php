<x-layouts.table-header title="Tag Teams">
    <x-slot:actions>
        @can('create', \App\Models\TagTeam::class)
            <x-buttons.primary size="sm"
                @click="$dispatch('openModal', { component: 'tag-teams.modals.form-modal' })">Add Tag
                Team</x-buttons.primary>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data enum="\App\Enums\Shared\EmploymentStatus" />
