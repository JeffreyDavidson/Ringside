<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="{{ $event->name }}">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('events.index')" label="Events" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :label="$event->name" />
        </x-toolbar>
    </x-slot>

    <div class="mb-5 card mb-xl-10" id="kt_profile_details_view">
        <!--begin::Card header-->
        <div class="card-header">
            <!--begin::Card title-->
            <div class="m-0 card-title">
                <h3 class="m-0 fw-bold">Event Details</h3>
            </div>
            <!--end::Card title-->
            <!--begin::Action-->
            <a href="{{ route('events.edit', $event) }}" class="btn btn-primary align-self-center">Edit Event</a>
            <!--end::Action-->
        </div>
        <!--begin::Card header-->
        <!--begin::Card body-->
        <div class="card-body p-9">
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Event Name</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $event->name }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Input group-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Date</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8 fv-row">
                    <span class="text-gray-800 fw-semibold fs-6">{{ $event->date?->format('Y-m-j g:i A') ?? 'Unscheduled' }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Input group-->
            <!--begin::Input group-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Venue</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <a href="#" class="text-gray-800 fw-semibold fs-6 text-hover-primary">{{ $event->venue->name ?? 'No Venue Chosen' }}</a>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Input group-->
            <!--begin::Input group-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Preview</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $event->preview ?? 'No preview added'}}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Input group-->
            @if ($event->isUnscheduled())
                <x-notice
                    title="This event needs your attention!"
                    description="This event does not have a date and needs to be scheduled."
                />
            @endif
        </div>
        <!--end::Card body-->
    </div>

    <div class="pt-3 mb-5 card mb-xl-10">
        <div class="card-header">
            <div class="card-title">
                <h2>Matches</h2>
            </div>
        </div>
        <div class="p-9 card-body">
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
</x-layouts.app>
