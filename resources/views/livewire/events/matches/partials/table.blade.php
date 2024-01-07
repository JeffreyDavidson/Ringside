@forelse($matches as $match)
    <div class="mb-12 d-flex flex-column align-items-center">
        @if ($loop->last)
            <h3>Main Event</h3>
        @else
            <h3>Match #{{ $loop->iteration }}</h3>
        @endif

        @if ($match->titles->isNotEmpty())
            <p>
                @foreach ($match->titles as $title)
                    <x-route-link
                        :route="route('titles.show', $title)"
                        label="{{ $title->name }}"
                    />

                    @if (! $loop->last)
                        @php echo " & " @endphp
                    @endif
                @endforeach

                {{ str('Championship')->plural($match->titles->count()) }}
            </p>
        @endif

        <p>{{ $match->matchType->name }} Match</p>

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

        <p>
            {{ str('Referee')->plural($match->referees->count()) }}:

            @foreach ($match->referees as $referee)
                <x-route-link
                    :route="route('referees.show', $referee)"
                    label="{{ $referee->full_name }}"
                />

                @if (! $loop->last)
                    @php echo " & " @endphp
                @endif
            @endforeach
        </p>

        <p class="mt-4">{{ $match->preview }}</p>
    </div>
@empty
    <p>No matches have been set for this event.</p>
@endforelse
