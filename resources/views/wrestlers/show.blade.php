<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="{{ $wrestler->name }}">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('wrestlers.index')" label="Wrestlers" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :label="$wrestler->name" />
        </x-toolbar>
    </x-slot>

    <div class="mb-5 card mb-xl-10" id="kt_profile_details_view">
        <!--begin::Card header-->
        <div class="card-header">
            <!--begin::Card title-->
            <div class="m-0 card-title">
                <h3 class="m-0 fw-bold">Wrestler Details</h3>
            </div>
            <!--end::Card title-->
            <!--begin::Action-->
            <a href="{{ route('wrestlers.edit', $wrestler) }}" class="btn btn-primary align-self-center">Edit Wrestler</a>
            <!--end::Action-->
        </div>
        <!--begin::Card header-->
        <!--begin::Card body-->
        <div class="card-body p-9">
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Wrestler Name</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $wrestler->name }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Height</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ floor($wrestler->height / 12) }}' {{ $wrestler->height % 12 }}"</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Weight</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $wrestler->weight }} lbs.</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Hometown</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $wrestler->hometown }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Signature Move</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $wrestler->signature_move }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Start Date</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8 fv-row">
                    <span class="text-gray-800 fw-semibold fs-6">{{ $wrestler->employedAt?->toDateString() ?? 'No Start Date Set' }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            @if ($wrestler->isUnemployed())
                <x-notice
                    title="This wrestler needs your attention!"
                    description="This wrestler does not have a start date and needs to be employed."
                />
            @endif
        </div>
        <!--end::Card body-->
    </div>
</x-layouts.app>
