<x-actions-dropdown>
    @can('view', $tagTeam)
        <x-buttons.view :route="route('tag-teams.show', $tagTeam)" />
    @endcan

    @can('update', $tagTeam)
        <x-buttons.edit :route="route('tag-teams.edit', $tagTeam)" />
    @endcan

    @can('delete', $tagTeam)
        <x-buttons.delete :route="route('tag-teams.destroy', $tagTeam)" />
    @endcan

    @if ($tagTeam->canBeRetired())
        @can('retire', $tagTeam)
            <x-buttons.retire :route="route('tag-teams.retire', $tagTeam)" />
        @endcan
    @endif

    @if ($tagTeam->canBeUnretired())
        @can('unretire', $tagTeam)
            <x-buttons.unretire :route="route('tag-teams.unretire', $tagTeam)" />
        @endcan
    @endif

    @if ($tagTeam->canBeEmployed())
        @can('employ', $tagTeam)
            <x-buttons.employ :route="route('tag-teams.employ', $tagTeam)" />
        @endcan
    @endif

    @if ($tagTeam->canBeReleased())
        @can('release', $tagTeam)
            <x-buttons.release :route="route('tag-teams.release', $tagTeam)" />
        @endcan
    @endif

    @if ($tagTeam->canBeSuspended())
        @can('suspend', $tagTeam)
            <x-buttons.suspend :route="route('tag-teams.suspend', $tagTeam)" />
        @endcan
    @endif

    @if ($tagTeam->canBeReinstated())
        @can('reinstate', $tagTeam)
            <x-buttons.reinstate :route="route('tag-teams.reinstate', $tagTeam)" />
        @endcan
    @endif


</x-actions-dropdown>
