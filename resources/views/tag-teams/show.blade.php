<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="{{ $tagTeam->name }}">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('tag-teams.index')" label="Tag Teams" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :label="$tagTeam->name" />
        </x-toolbar>
    </x-slot>

    <div class="mb-5 card mb-xl-10" id="kt_profile_details_view">
        <!--begin::Card header-->
        <div class="card-header">
            <!--begin::Card title-->
            <div class="m-0 card-title">
                <h3 class="m-0 fw-bold">Tag Team Details</h3>
            </div>
            <!--end::Card title-->
            <!--begin::Action-->
            <a href="{{ route('tag-teams.edit', $tagTeam) }}" class="btn btn-primary align-self-center">Edit Tag Team</a>
            <!--end::Action-->
        </div>
        <!--begin::Card header-->
        <!--begin::Card body-->
        <div class="card-body p-9">
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Tag Team Name</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $tagTeam->name }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin:Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Start Date</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8 fv-row">
                    <span class="text-gray-800 fw-semibold fs-6">{{ $tagTeam->startedAt?->toDateString() ?? 'No Start Date Set' }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            @unless ($tagTeam->currentWrestlers)
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Combined Weight</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $tagTeam->combined_weight }} lbs.</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            @endunless
            @if ($tagTeam->signature_move)
            <!--begin:Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Signature Move</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8 fv-row">
                    <span class="text-gray-800 fw-semibold fs-6">{{ $tagTeam->signature_move }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            @endif
            @if ($tagTeam->isUnemployed())
                <x-notice
                    title="This tag team needs your attention!"
                    description="This tag team does not have a start date and needs to be employed."
                />
            @endif
        </div>
        <!--end::Card body-->
    </div>
</x-layouts.app>
