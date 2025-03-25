@props(['match', 'loop'])

<div class="mb-12 d-flex flex-column align-items-center">
    @if ($loop->last)
        <h3>Main Event</h3>
    @else
        <h3>Match #{{ $loop->iteration }}</h3>
    @endif

    @if ($match->titles->isNotEmpty())
        <x-matches.titles-list />
    @endif

    <p>{{ $match->matchType->name }} Match</p>

    <div class="flex-row">
        <x-matches.competitors-list />
    </div>

    <x-matches.referees-list />

    <p class="mt-4">{{ $match->preview }}</p>
</div>
