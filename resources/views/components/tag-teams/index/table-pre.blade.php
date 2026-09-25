<header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div class="flex min-w-0 flex-col gap-2">
        <h1 class="font-display text-ringside-ink m-0 text-3xl leading-none tracking-tight">
            {{ __('tag-teams.index_title') }}
        </h1>
        <p class="text-ringside-muted m-0 max-w-2xl text-sm leading-6">{{ __('tag-teams.index_description') }}</p>
    </div>

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
</header>
