@forelse($matches as $match)
    <div class="mb-12 d-flex flex-column align-items-center">
        @if ($loop->last)
            <h3>Main Event</h3>
        @else
            <h3>Match #{{ $loop->iteration }}</h3>
        @endif

        @if ($match->titles->isNotEmpty())
            <p>{{ $match->titles->pluck('name')->implode(', ') }} Championship Match</p>
        @else
            <p>{{ $match->matchType->name }} Match</p>
        @endif

        <p>Refereed By {{ $match->referees->pluck('full_name')->implode(', ') }}</p>

        <div class="flex-row">
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
        </div>

        <p class="mt-4">{{ $match->preview }}</p>
    </div>
@empty
    <p>No matches have been set for this event.</p>
@endforelse
