<div class="dropdown dropdown-inline" data-toggle="kt-tooltip" title="" data-placement="right" data-original-title="Quick filters">
    <a href="#" class="btn btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="flaticon-interface-7"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-fit dropdown-menu-md dropdown-menu-right" x-placement="bottom-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(-224.75px, 35.25px, 0px);">
        <!--begin::Nav-->
        <form class="kt-nav">
            <div class="kt-nav__head">
                Filter Options:
            </div>
            <div class="kt-nav__separator"></div>
             {{ $slot }}
            <div class="kt-nav__separator"></div>
            <div class="kt-nav__foot">
                <a class="btn btn-label-brand btn-bold btn-sm" href="#" id="applyFilters">Apply Filters</a>
                <a class="btn btn-clean btn-bold btn-sm" href="#" id="clearFilters">Clear Filters</a>
            </div>
        </form>
        <!--end::Nav-->
    </div>
</div>
