<header class="h-[70px] fixed top-0 z-10 left-0 right-0 flex items-stretch shrink-0 bg-[#fefefe] lg:start-[280px]">
    <!-- Container -->
    <x-container-fixed class="flex justify-between items-stretch lg:gap-4">
        <!-- Mobile Logo -->
        <div class="flex gap-1 lg:hidden items-center -ml-1">
            <a class="shrink-0" href="{{ route('dashboard') }}">
                <img class="max-h-[25px] w-full" src="{{ Vite::image('app/mini-logo.svg') }}" />
            </a>
            <div class="flex items-center">
                <button class="btn btn-icon btn-light btn-clear btn-sm">
                    <i class="ki-filled ki-menu"></i>
                </button>
                <button class="btn btn-icon btn-light btn-clear btn-sm">
                    <i class="ki-filled ki-burger-menu-2"></i>
                </button>
            </div>
        </div>
        <!-- End of Mobile Logo -->
        <!-- Mega Menu -->
        <x-mega-menu/>
        <!-- End of Mega Menu -->
        <!-- Topbar -->
        <x-topbar />
        <!-- End of Topbar -->
    </x-container-fixed>
    <!-- End of Container -->
</header>
