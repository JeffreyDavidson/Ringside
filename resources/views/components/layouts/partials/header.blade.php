<header
    x-data="{ atTop: true }"
    @scroll.window="atTop = window.pageYOffset > 1 ? false : true"
    class="fixed start-0 end-0 top-0 z-10 flex h-[--header-height] shrink-0 items-stretch bg-[--page-bg] lg:start-[280px] lg:h-[--header-height]"
    :class="atTop === false ? 'shadow-sm' : ''"
>
    <!-- Container -->
    <x-container-fixed class="flex items-stretch justify-between lg:gap-4">
        <!-- Mobile Logo & Menu Toggle -->
        <div class="-ms-1 flex items-center gap-1 lg:hidden">
            <a class="shrink-0" href="{{ route('dashboard') }}">
                <span class="text-lg font-bold text-gray-900">Ringside</span>
            </a>
            <div class="flex items-center">
                <button
                    @click="$store.sidebar && $store.sidebar.openMobile()"
                    class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md border border-transparent leading-none text-gray-700"
                >
                    <x-heroicon-o-bars-3 class="size-5 text-gray-600" />
                </button>
            </div>
        </div>
        <!-- Topbar -->
        <x-topbar />
    </x-container-fixed>
</header>
