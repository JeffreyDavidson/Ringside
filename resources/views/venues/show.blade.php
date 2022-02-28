<x-layouts.app>
    <x-slot name="toolbar">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ $venue->name }} Page
        </h2>
    </x-slot>
    <x-content>
        <div class="mb-5 card mb-xl-10" id="kt_profile_details_view">
            <!--begin::Card header-->
            <div class="card-header">
                <!--begin::Card title-->
                <div class="m-0 card-title">
                    <h3 class="m-0 fw-bolder">{{ $venue->name }} Details</h3>
                </div>
                <!--end::Card title-->
                <!--begin::Action-->
                <a href="{{ route('venues.edit', $venue) }}" class="btn btn-primary align-self-center">Edit Details</a>
                <!--end::Action-->
            </div>
            <!--begin::Card header-->
            <!--begin::Card body-->
            <div class="card-body p-9">
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">Name</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="text-gray-800 fw-bolder fs-6">{{ $venue->name }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">Address</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="text-gray-800 fw-bolder fs-6">{{ $venue->address1 }} {{ $venue->address2 }}</span>
                        <span class="text-gray-800 fw-bolder fs-6">{{ $venue->city }}, {{ $venue->state }} {{ $venue->zip }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
            </div>
            <!--end::Card body-->
        </div>
    </x-content>
</x-layouts.app>
