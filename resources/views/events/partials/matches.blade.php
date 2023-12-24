<x-card class="pt-3">
    <x-slot name="header">
        <x-card.header title="Matches" />
    </x-slot>

    <x-card.body>
        @forelse($matches as $match)
            <div class="mb-12">
                @if ($loop->last)
                    <h3>Main Event</h3>
                @else
                    <h3>Match #{{ $loop->iteration }}</h3>
                @endif

                <p>Refereed By {{ $match->referees->pluck('full_name')->implode(', ') }}</p>
                @if ($match->titles->isNotEmpty())
                    <p>{{ $match->titles->pluck('name')->implode(', ') }} Championship Match</p>
                @else
                    <p>{{ $match->matchType->name }} Match</p>
                @endif

                <p>{{ $match->competitors->groupedBySide()->map(function ($side) {
                        return $side->pluck('competitor.name')->implode(' & ');
                    })->implode(' vs. ') }}</p>

                <p>{{ $match->preview }}</p>
            </div>
        @empty
            <p>No matches have been set for this event.</p>
        @endforelse
    </x-card.body>
</x-card>
