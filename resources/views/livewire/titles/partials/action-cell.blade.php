<x-actions-dropdown>
    @can('view', $title)
        <x-buttons.view :route="route('titles.show', $title)" />
    @endcan

    @can('update', $title)
        <x-buttons.edit :route="route('titles.edit', $title)" />
    @endcan

    @can('delete', $title)
        <x-buttons.delete :route="route('titles.destroy', $title)" />
    @endcan

{{--    @if ($title->canBeRetired())--}}
{{--        @can('retire', $title)--}}
{{--            <x-buttons.retire :route="route('titles.retire', $title)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($title->canBeUnretired())--}}
{{--        @can('unretire', $title)--}}
{{--            <x-buttons.unretire :route="route('titles.unretire', $title)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($title->canBeActivated())--}}
{{--        @can('activate', $title)--}}
{{--            <x-buttons.activate :route="route('titles.activate', $title)" />--}}
{{--        @endcan--}}
{{--    @endif--}}

{{--    @if ($title->canBeDeactivated())--}}
{{--        @can('deactivate', $title)--}}
{{--            <x-buttons.deactivate :route="route('titles.deactivate', $title)" />--}}
{{--        @endcan--}}
{{--    @endif--}}
</x-actions-dropdown>
