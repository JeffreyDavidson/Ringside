{{-- Desktop Sidebar --}}
<aside
    x-data="{
        expanded: $store.sidebar ? $store.sidebar.expanded : true,
        init() {
            if ($store.sidebar) {
                this.$watch('$store.sidebar.expanded', (value) => (this.expanded = value));
            }
        },
        toggle() {
            if ($store.sidebar) $store.sidebar.toggle();
        },
    }"
    @mouseenter="$store.sidebar && ($store.sidebar.hovered = true)"
    @mouseleave="$store.sidebar && ($store.sidebar.hovered = false)"
    :class="expanded ? 'w-[280px]' : 'w-[80px] hover:w-[280px]'"
    :data-collapsed="! expanded"
    class="bg-background border-e-border group fixed top-0 bottom-0 z-20 hidden shrink-0 flex-col items-stretch border-e transition-all duration-300 lg:flex"
    :aria-label="expanded ? 'Main navigation' : 'Main navigation (collapsed)'"
>
    {{-- Sidebar Header --}}
    <div class="relative hidden h-[70px] shrink-0 items-center justify-between px-3 lg:flex lg:px-6">
        <a href="{{ route('dashboard') }}">
            <img
                class="default-logo min-h-[22px] max-w-none transition-opacity duration-200"
                :class="expanded ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                src="{{ Vite::image('app/default-logo.svg') }}"
                alt="{{ config('app.name') }}"
            />
            <img
                class="small-logo absolute top-1/2 left-6 min-h-[22px] max-w-none -translate-y-1/2 transition-opacity duration-200"
                :class="expanded ? 'opacity-0' : 'opacity-100 group-hover:opacity-0'"
                src="{{ Vite::image('app/mini-logo.svg') }}"
                alt="{{ config('app.name') }}"
            />
        </a>

        {{-- Toggle Button --}}
        <button
            @click="toggle()"
            @keydown.escape="$store.sidebar && ($store.sidebar.expanded = false)"
            :aria-expanded="expanded"
            aria-label="Toggle sidebar navigation"
            class="border-border bg-background hover:bg-accent hover:text-accent-foreground focus-visible:ring-primary absolute start-full top-2/4 inline-flex size-[30px] -translate-x-2/4 -translate-y-2/4 cursor-pointer items-center justify-center rounded-lg border transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
        >
            <x-heroicon-s-chevron-left
                class="text-muted-foreground size-4 transition-all duration-300"
                x-bind:class="expanded ? '' : 'rotate-180'"
            />
        </button>
    </div>

    {{-- Sidebar Content --}}
    <div class="flex shrink-0 grow py-5 pe-2">
        <div class="scrollbar-thumb-muted hover:scrollbar-thumb-muted-foreground flex shrink-0 grow scrollbar-thin scrollbar-track-transparent overflow-y-auto ps-2 pe-1 lg:ps-5 lg:pe-3">
            <x-sidebar.menu />
        </div>
    </div>
</aside>

{{-- Mobile Sidebar Drawer --}}
<div x-data x-show="$store.sidebar && $store.sidebar.mobileOpen" x-cloak class="fixed inset-0 z-50 lg:hidden">
    {{-- Backdrop --}}
    <div
        x-show="$store.sidebar && $store.sidebar.mobileOpen"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$store.sidebar && $store.sidebar.closeMobile()"
        class="absolute inset-0 bg-black/50"
    ></div>

    {{-- Drawer --}}
    <aside
        x-show="$store.sidebar && $store.sidebar.mobileOpen"
        x-transition:enter="transition-transform ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        @keydown.escape.window="$store.sidebar && $store.sidebar.closeMobile()"
        class="bg-background border-e-border relative flex h-full w-[280px] flex-col border-e"
    >
        {{-- Mobile Logo Area --}}
        <div class="border-border flex h-[60px] shrink-0 items-center justify-between border-b px-6">
            <a href="{{ route('dashboard') }}">
                <img
                    class="min-h-[22px] max-w-none"
                    src="{{ Vite::image('app/default-logo.svg') }}"
                    alt="{{ config('app.name') }}"
                />
            </a>

            {{-- Close Button --}}
            <button
                @click="$store.sidebar && $store.sidebar.closeMobile()"
                aria-label="Close navigation"
                class="text-muted-foreground hover:text-foreground hover:bg-accent focus-visible:ring-primary inline-flex size-8 cursor-pointer items-center justify-center rounded-lg transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
            >
                <x-heroicon-o-x-mark class="size-5" />
            </button>
        </div>

        {{-- Mobile Menu Area --}}
        <div class="flex shrink-0 grow py-5 pe-2">
            <div class="flex shrink-0 grow overflow-y-auto ps-5 pe-3">
                <x-sidebar.menu />
            </div>
        </div>
    </aside>
</div>
