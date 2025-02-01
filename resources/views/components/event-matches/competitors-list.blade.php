@aware(['match'])

@foreach ($match->competitors->propertlyFormattedCompetitors() as $eventMatchCompetitors)
    @foreach ($eventMatchCompetitors as $eventMatchCompetitor)
        @php
            $competitor = $eventMatchCompetitor->competitor;
            $resource = str($competitor->getTable())->replace('_', '-')->value();
        @endphp

        <x-route-link
            :route="route($resource.'.show', $competitor)"
            label="{{ $competitor->name }}"
        />

        @if (! $loop->last)
            @php echo " & " @endphp
        @endif
    @endforeach

    @if (! $loop->last)
        @php echo " vs. " @endphp
    @endif
@endforeach
