<header x-data="{ atTop: true }" @scroll.window="atTop = window.pageYOffset > 1 ? false : true"
    class="h-[--header-height] lg:h-[--header-height] fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-[--page-bg] lg:start-[280px]"
    :class="(atTop === false) ? 'shadow-sm' : ''">
    <!-- Container -->
    <x-container-fixed class="flex justify-between items-stretch lg:gap-4">
        <!-- Mobile Logo -->
        <div class="flex gap-1 lg:hidden items-center -ms-1">
            <a class="shrink-0" href="{{ route('dashboard') }}">
                <img class="max-h-[25px] w-full" src="{{ Vite::image('app/mini-logo.svg') }}" />
            </a>
            <div class="flex items-center">
                <button
                    class="inline-flex items-center cursor-pointer leading-none rounded-md border border-solid border-transparent outline-none justify-center shrink-0 p-0 gap-0 size-8 bg-transparent text-gray-700 ps-3 pe-3 font-medium text-xs">
                    <i class="ki-filled ki-menu text-lg text-gray-600 leading-none"></i>
                </button>
            </div>
        </div>
        <!-- End of Mobile Logo -->
        <!-- Breadcrumbs -->
        <x-breadcrumbs class="hidden lg:flex" />
        <!-- End of Breadcrumbs -->
        <!-- Topbar -->
        <x-topbar />
        <!-- End of Topbar -->
    </x-container-fixed>
    <!-- End of Container -->
</header>
