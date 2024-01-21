<div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px show" {{ $attributes }}
style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate3d(-646.5px, 230px, 0px);">
    <div class="px-7 py-5">
        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
    </div>

    <x-separator class="border-gray-200"/>

    <div class="px-7 py-5">

        {{ $slot }}

        <div class="d-flex justify-content-end">
            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6">
                Reset
            </button>
            <button type="submit" class="btn btn-primary fw-semibold px-6">
                Apply
            </button>
        </div>
    </div>
</div>
