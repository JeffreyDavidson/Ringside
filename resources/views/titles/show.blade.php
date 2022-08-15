<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="{{ $title->name }}">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('titles.index')" label="Titles" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :label="$title->name" />
        </x-toolbar>
    </x-slot>

    <div class="mb-5 card mb-xl-10" id="kt_profile_details_view">
        <!--begin::Card header-->
        <div class="card-header">
            <!--begin::Card title-->
            <div class="m-0 card-title">
                <h3 class="m-0 fw-bold">Title Details</h3>
            </div>
            <!--end::Card title-->
            <!--begin::Action-->
            <a href="{{ route('titles.edit', $title) }}" class="btn btn-primary align-self-center">Edit Title</a>
            <!--end::Action-->
        </div>
        <!--begin::Card header-->
        <!--begin::Card body-->
        <div class="card-body p-9">
            <!--begin::Row-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Title Name</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8">
                    <span class="text-gray-800 fs-6">{{ $title->name }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Input group-->
            <div class="row mb-7">
                <!--begin::Label-->
                <label class="col-lg-4 fw-semibold text-muted">Activation Date</label>
                <!--end::Label-->
                <!--begin::Col-->
                <div class="col-lg-8 fv-row">
                    <span class="text-gray-800 fw-semibold fs-6">{{ $title->activatedAt->toDateString() ?? 'Unscheduled' }}</span>
                </div>
                <!--end::Col-->
            </div>
            <!--end::Input group-->
            @if ($title->isUnactivated())
                <x-notice
                    title="This title needs your attention!"
                    description="This title does not have an activation date and needs to be activated."
                />
            @endif
        </div>
        <!--end::Card body-->
    </div>

    <livewire:titles.title-championships-list :title="$title" />
</x-layouts.app>
