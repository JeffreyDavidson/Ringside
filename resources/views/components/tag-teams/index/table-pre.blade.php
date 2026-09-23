<x-layouts.table-header title="Tag Teams" :subtitle="__('tag-teams.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Roster\TagTeams\TagTeam::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11"
                @click="$dispatch('openModal', { component: 'tag-teams.modals.form-modal' })"
            >
                Add Tag Team
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data />
