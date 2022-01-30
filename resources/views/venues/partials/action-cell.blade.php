<x-actions-dropdown>
    <div class="py-4 menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-125px show" data-kt-menu="true" style="z-index: 105; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate3d(-374px, 357px, 0px);" data-popper-placement="bottom-end">
        <!--begin::Menu item-->
        @can('update', $venue)
            <div class="px-3 menu-item">
                <x-buttons.edit :route="route('venues.edit', $venue)" />
            </div>
        @endcan
        <!--end::Menu item-->
        <!--begin::Menu item-->
        @can('delete', $venue)
            <div class="px-3 menu-item">
                <x-buttons.delete table="venue" :route="route('venues.destroy', $venue)" />
            </div>
        @endcan
        <!--end::Menu item-->
    </div>
</x-actions-dropdown>
