<x-layouts.app>
    <x-slot name="toolbar">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ $event->name }} Page
        </h2>
    </x-slot>

    <x-content>
        @if ($event->isUnscheduled())
            <div class="alert alert-warning" role="alert">
                <strong>Warning!</strong>&nbsp;This event is not scheduled!
            </div>
        @endif

        <div class="mb-5 card mb-xl-10" id="kt_profile_details_view">
            <div class="card-header">
                <div class="m-0 card-title">
                    <h3 class="m-0 fw-bolder">{{ $event->name }} Details</h3>
                </div>
                @can('update', $event)
                    <a href="{{ route('events.edit', $event) }}" class="btn btn-primary align-self-center">Edit Details</a>
                @endcan
            </div>

            <div class="card-body p-9">
                <div class="row mb-7">
                    <label class="col-lg-4 fw-bold text-muted">Name</label>

                    <div class="col-lg-8">
                        <span class="text-gray-800 fw-bolder fs-6">{{ $event->name }}</span>
                    </div>
                </div>

                @if ($event->isScheduled() || $event->isPast())
                    <div class="row mb-7">
                        <label class="col-lg-4 fw-bold text-muted">Date</label>

                        <div class="col-lg-8">
                            <span class="text-gray-800 fw-bolder fs-6">{{ $event->date->format('Y-m-j g:i A') }}</span>
                        </div>
                    </div>
                @endif

                @if ($event->venue)
                    <div class="row mb-7">
                        <label class="col-lg-4 fw-bold text-muted">Venue</label>

                        <div class="col-lg-8">
                            <span class="text-gray-800 fw-bolder fs-6">{{ $event->venue->name }}</span>
                        </div>
                    </div>
                @endif

                @if ($event->preview)
                    <div class="row mb-7">
                        <label class="col-lg-4 fw-bold text-muted">Preview</label>

                        <div class="col-lg-8">
                            <p class="text-gray-800 fw-bolder fs-6">{{ $event->preview }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="pt-3 mb-5 card card-flush mb-xl-10">
            <div class="card-header">
                <div class="card-title">
                    <h2>Matches</h2>
                </div>
            </div>
            <div class="pt-2 card-body">
                @forelse($event->matches as $match)
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
            </div>
        </div>
    </x-content>
</x-layouts.app>
