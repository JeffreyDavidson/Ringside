<x-actions-dropdown>
    @can('view', $stable)
        <x-buttons.view :route="route('stables.show', $stable)" />
    @endcan

    @can('update', $stable)
        <x-buttons.edit :route="route('stables.edit', $stable)" />
    @endcan

    @can('delete', $stable)
        <x-buttons.delete :route="route('stables.destroy', $stable)" />
    @endcan

{{--    @if ($stable->canBeRetired())--}}
{{--        @can('retire', $stable)--}}
{{--            <x-buttons.retire :route="route('stables.retire', $stable)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($stable->canBeUnretired())--}}
{{--        @can('unretire', $stable)--}}
{{--            <x-buttons.unretire :route="route('stables.unretire', $stable)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($stable->canBeActivated())--}}
{{--        @can('activate', $stable)--}}
{{--            <x-buttons.activate :route="route('stables.activate', $stable)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($stable->canBeDeactivated())--}}
{{--        @can('release', $stable)--}}
{{--            <x-buttons.deactivate :route="route('stables.deactivate', $stable)" />--}}
{{--        @endcan--}}
{{--    @endif--}}
</x-actions-dropdown>
