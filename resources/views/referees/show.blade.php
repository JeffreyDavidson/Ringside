<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="{{ $referee->full_name }}">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('referees.index')" label="Referees" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :label="$referee->full_name" />
        </x-toolbar>
    </x-slot>

    <div class="mb-5 card mb-xl-10" id="kt_profile_details_view">
        <!--begin::Card header-->
        <div class="card-header">
            <!--begin::Card title-->
            <div class="m-0 card-title">
                <h3 class="m-0 fw-bold">Referee Details</h3>
            </div>
            <!--end::Card title-->
            <!--begin::Action-->
            <a href="{{ route('referees.edit', $referee) }}" class="btn btn-primary align-self-center">Edit Referee</a>
            <!--end::Action-->
        </div>
        <!--begin::Card header-->
        <!--begin::Card body-->
        <div class="card-body p-9">
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Referee Name</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $referee->full_name }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Input group-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Start Date</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8 fv-row">
                    <span class="text-gray-800 fw-semibold fs-6">{{ $referee->startedAt?->toDateString() ?? 'No Start Date Set' }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Input group-->
            @if ($referee->isUnemployed())
                <x-notice
                    title="This referee needs your attention!"
                    description="This referee does not have a start date and needs to be employed."
                />
            @endif
        </div>
        <!--end::Card body-->
    </div>
</x-layouts.app>
