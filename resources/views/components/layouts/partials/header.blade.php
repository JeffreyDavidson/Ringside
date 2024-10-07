<header
    class="header fixed top-0 z-10 left-0 right-0 flex items-stretch shrink-0 bg-[--tw-page-bg] dark:bg-[--tw-page-bg-dark]"
    data-sticky="true" data-sticky-class="shadow-sm" data-sticky-name="header" id="header">
    <!-- Container -->
    <div class="container-fixed flex justify-end lg:gap-4" id="header_container">
        <!-- Mobile Logo -->
        <div class="flex gap-1 lg:hidden items-center -ml-1">
            <a class="shrink-0" href="html/demo1.html">
                <img class="max-h-[25px] w-full" src="{{ asset('assets/media/app/mini-logo.svg') }}" />
            </a>
            <div class="flex items-center">
                <button class="btn btn-icon btn-light btn-clear btn-sm" data-drawer-toggle="#sidebar">
                    <i class="ki-filled ki-menu"></i>
                </button>
                <button class="btn btn-icon btn-light btn-clear btn-sm" data-drawer-toggle="#mega_menu_wrapper">
                    <i class="ki-filled ki-burger-menu-2"></i>
                </button>
            </div>
        </div>
        <!-- End of Mobile Logo -->
        <!-- Topbar -->
        <x-topbar />
        <!-- End of Topbar -->
    </div>
    <!-- End of Container -->
</header>
