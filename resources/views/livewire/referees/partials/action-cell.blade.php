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

{{--    @if ($referee->canBeRetired())--}}
{{--        @can('retire', $referee)--}}
{{--            <x-buttons.retire :route="route('referees.retire', $referee)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($referee->canBeUnretired())--}}
{{--        @can('unretire', $referee)--}}
{{--            <x-buttons.unretire :route="route('referees.unretire', $referee)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($referee->canBeEmployed())--}}
{{--        @can('employ', $referee)--}}
{{--            <x-buttons.employ :route="route('referees.employ', $referee)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($referee->canBeReleased())--}}
{{--        @can('release', $referee)--}}
{{--            <x-buttons.release :route="route('referees.release', $referee)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($referee->canBeSuspended())--}}
{{--        @can('suspend', $referee)--}}
{{--            <x-buttons.suspend :route="route('referees.suspend', $referee)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($referee->canBeReinstated())--}}
{{--        @can('reinstate', $referee)--}}
{{--            <x-buttons.reinstate :route="route('referees.reinstate', $referee)" />--}}
{{--        @endcan--}}
{{--    @endif--}}


{{--    @if ($referee->canBeInjured())--}}
{{--        @can('injure', $referee)--}}
{{--            <x-buttons.injure :route="route('referees.injure', $referee)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($referee->canBeClearedFromInjury())--}}
{{--        @can('clearFromInjury', $referee)--}}
{{--            <x-buttons.recover :route="route('referees.clear-from-injury', $referee)" />--}}
{{--        @endcan--}}
{{--    @endif--}}
</x-actions-dropdown>
