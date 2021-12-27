<x-actions-dropdown>
    @can('view', $referee)
        <x-buttons.view :route="route('referees.show', $referee)" />
    @endcan

    @can('update', $referee)
        <x-buttons.edit :route="route('referees.edit', $referee)" />
    @endcan

    @can('delete', $referee)
        <x-buttons.delete :route="route('referees.destroy', $referee)" />
    @endcan

    @if ($referee->canBeRetired())
        @can('retire', $referee)
            <x-buttons.retire :route="route('referees.retire', $referee)" />
        @endcan
    @endif

    @if ($referee->canBeUnretired())
        @can('unretire', $referee)
            <x-buttons.unretire :route="route('referees.unretire', $referee)" />
        @endcan
    @endif

    @can('employ', $referee)
        @if ($referee->canBeEmployed())
            <x-buttons.employ :route="route('referees.employ', $referee)" />
        @endif
    @endcan

    @can('suspend', $referee)
        @if ($referee->canBeSuspended())
            <x-buttons.suspend :route="route('referees.suspend', $referee)" />
        @endif
    @endcan

    @can('reinstate', $referee)
        @if ($referee->canBeReinstated())
            <x-buttons.reinstate :route="route('referees.reinstate', $referee)" />
        @endif
    @endcan

    @can('injure', $referee)
        @if ($referee->canBeInjured())
            <x-buttons.injure :route="route('referees.injure', $referee)" />
        @endif
    @endcan

    @can('recover', $referee)
        @if ($referee->canBeClearedFromInjury())
            <x-buttons.recover :route="route('referees.recover', $referee)" />
        @endif
    @endcan
</x-actions-dropdown>
