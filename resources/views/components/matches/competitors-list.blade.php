@aware(['match'])

@foreach ($match->competitors->propertlyFormattedCompetitors() as $MatchCompetitors)
    @foreach ($MatchCompetitors as $MatchCompetitor)
        @php
            $competitor = $MatchCompetitor->competitor;
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
