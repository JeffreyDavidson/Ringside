<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="{{ $stable->name }}">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('stables.index')" label="Stables" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :label="$stable->name" />
        </x-toolbar>
    </x-slot>

    <div class="mb-5 card mb-xl-10" id="kt_profile_details_view">
        <!--begin::Card header-->
        <div class="card-header">
            <!--begin::Card title-->
            <div class="m-0 card-title">
                <h3 class="m-0 fw-bold">Stable Details</h3>
            </div>
            <!--end::Card title-->
            <!--begin::Action-->
            <a href="{{ route('stables.edit', $stable) }}" class="btn btn-primary align-self-center">Edit Stable</a>
            <!--end::Action-->
        </div>
        <!--begin::Card header-->
        <!--begin::Card body-->
        <div class="card-body p-9">
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Stable Name</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $stable->name }}</span>
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
                    <span class="text-gray-800 fw-semibold fs-6">{{ $stable->activatedAt->toDateString() ?? 'No Start Date Set' }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Input group-->
            @if ($stable->isUnactivated())
                <x-notice
                    title="This stable needs your attention!"
                    description="This stable does not have a start date and needs to be started."
                />
            @endif
        </div>
        <!--end::Card body-->
    </div>
</x-layouts.app>
