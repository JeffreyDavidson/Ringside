@props([
    "collapsibleLink",
    "href",
    "resource"
])

<div class="d-flex flex-stack fs-4 py-3">
    <div class="fw-bold rotate collapsible active"
         data-bs-toggle="collapse"
         href="#{{ $collapsibleLink }}"
         role="button"
         aria-expanded="true"
         aria-controls="{{ $collapsibleLink }}"
    >
        Details
        <span class="ms-2 rotate-180">
            <i class="ki-duotone ki-down fs-3"></i>
        </span>
    </div>

    <span data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Edit {{ $resource }} details">
        <a href="{{ $href }}" class="btn btn-sm btn-light-primary">
            Edit
        </a>
    </span>
</div>
