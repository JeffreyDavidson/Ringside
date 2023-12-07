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

    @can('employ', $manager)
        @if ($manager->canBeEmployed())
            <x-buttons.employ :route="route('managers.employ', $manager)" />
        @endif
    @endcan

    @can('suspend', $manager)
        @if ($manager->canBeSuspended())
            <x-buttons.suspend :route="route('managers.suspend', $manager)" />
        @endif
    @endcan

    @can('reinstate', $manager)
        @if ($manager->canBeReinstated())
            <x-buttons.reinstate :route="route('managers.reinstate', $manager)" />
        @endif
    @endcan

    @can('injure', $manager)
        @if ($manager->canBeInjured())
            <x-buttons.injure :route="route('managers.injure', $manager)" />
        @endif
    @endcan

    @can('recover', $manager)
        @if ($manager->canBeClearedFromInjury())
            <x-buttons.recover :route="route('managers.recover', $manager)" />
        @endif
    @endcan
</x-actions-dropdown>
