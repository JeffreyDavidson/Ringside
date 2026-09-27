<x-layouts.table-header :title="__('tag-teams.index_title')" :subtitle="__('tag-teams.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Roster\TagTeams\TagTeam::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11 shrink-0"
                @click="$dispatch('openModal', { component: 'tag-teams.modals.form-modal' })"
            >
                <x-heroicon-o-plus class="size-4" aria-hidden="true" />
                {{ __('tag-teams.add') }}
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>
