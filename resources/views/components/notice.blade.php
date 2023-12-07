@props([
    'title',
    'description'
])

<!--begin::Notice-->
<div class="p-6 border border-dashed rounded notice d-flex bg-light-warning border-warning">
    <!--begin::Icon-->
    <!--begin::Svg Icon | path: icons/duotune/general/gen044.svg-->
    <span class="svg-icon svg-icon-2tx svg-icon-warning me-4">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor"></rect>
            <rect x="11" y="14" width="7" height="2" rx="1" transform="rotate(-90 11 14)" fill="currentColor"></rect>
            <rect x="11" y="17" width="2" height="2" rx="1" transform="rotate(-90 11 17)" fill="currentColor"></rect>
        </svg>
    </span>
    <!--end::Svg Icon-->
    <!--end::Icon-->
    <!--begin::Wrapper-->
    <div class="d-flex flex-stack flex-grow-1">
        <!--begin::Content-->
        <div class="fw-semibold">
            <h4 class="text-gray-900 fw-bold">{{ $title }}</h4>
            <div class="text-gray-700 fs-6">{{ $description }}</div>
        </div>
        <!--end::Content-->
    </div>
    <!--end::Wrapper-->
</div>
<!--end::Notice-->
