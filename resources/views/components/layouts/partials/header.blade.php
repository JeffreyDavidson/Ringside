<header x-data="{ atTop: true }" @scroll.window="atTop = window.pageYOffset > 1 ? false : true"
    class="h-[--header-height] lg:h-[--header-height] fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-[--page-bg] lg:start-[280px]"
    :class="(atTop === false) ? 'shadow-sm' : ''">
    <!-- Container -->
    <x-container-fixed class="flex justify-between items-stretch lg:gap-4">
        <!-- Mobile Logo & Menu Toggle -->
        <div class="flex gap-1 lg:hidden items-center -ms-1">
            <a class="shrink-0" href="{{ route('dashboard') }}">
                <span class="text-lg font-bold text-gray-900">Ringside</span>
            </a>
            <div class="flex items-center">
                <button
                    @click="$store.sidebar && $store.sidebar.openMobile()"
                    class="inline-flex items-center cursor-pointer leading-none rounded-md border border-transparent justify-center shrink-0 size-8 text-gray-700"
                >
                    <x-heroicon-o-bars-3 class="size-5 text-gray-600" />
                </button>
            </div>
        </div>
        <!-- Topbar -->
        <x-topbar />
    </x-container-fixed>
</header>
