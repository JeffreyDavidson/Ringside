<x-actions-dropdown>
    @can('view', $manager)
        <x-buttons.view :route="route('managers.show', $manager)" />
    @endcan

    @can('update', $manager)
        <x-buttons.edit :route="route('managers.edit', $manager)" />
    @endcan

    @can('delete', $manager)
        <x-buttons.delete :route="route('managers.destroy', $manager)" />
    @endcan

    @if ($manager->canBeRetired())
        @can('retire', $manager)
            <x-buttons.retire :route="route('managers.retire', $manager)" />
        @endcan
    @endif

    @if ($manager->canBeUnretired())
        @can('unretire', $manager)
            <x-buttons.unretire :route="route('managers.unretire', $manager)" />
        @endcan
    @endif

    @if ($manager->canBeEmployed())
        @can('employ', $manager)
            <x-buttons.employ :route="route('managers.employ', $manager)" />
        @endcan
    @endif

    @if ($manager->canBeReleased())
        @can('release', $manager)
            <x-buttons.release :route="route('managers.release', $manager)" />
        @endcan
    @endif

    @if ($manager->canBeSuspended())
        @can('suspend', $manager)
            <x-buttons.suspend :route="route('managers.suspend', $manager)" />
        @endcan
    @endif

    @if ($manager->canBeReinstated())
        @can('reinstate', $manager)
            <x-buttons.reinstate :route="route('managers.reinstate', $manager)" />
        @endcan
    @endif


    @if ($manager->canBeInjured())
        @can('injure', $manager)
            <x-buttons.injure :route="route('managers.injure', $manager)" />
        @endcan
    @endif

    @if ($manager->canBeClearedFromInjury())
        @can('clearFromInjury', $manager)
            <x-buttons.recover :route="route('managers.clear-from-injury', $manager)" />
        @endcan
    @endif
</x-actions-dropdown>
