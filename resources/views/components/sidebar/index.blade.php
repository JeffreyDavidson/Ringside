{{-- Desktop Sidebar --}}
<aside x-data="{
        expanded: $store.sidebar ? $store.sidebar.expanded : true,
        init() {
            if ($store.sidebar) {
                this.$watch('$store.sidebar.expanded', value => this.expanded = value);
            }
        },
        toggle() {
            if ($store.sidebar) $store.sidebar.toggle();
        }
    }"
    @mouseenter="$store.sidebar && ($store.sidebar.hovered = true)"
    @mouseleave="$store.sidebar && ($store.sidebar.hovered = false)"
    :class="expanded ? 'w-[280px]' : 'w-[80px] hover:w-[280px]'"
    :data-collapsed="!expanded"
    class="bg-background border-e border-e-border fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 transition-all duration-300 group"
    :aria-label="expanded ? 'Main navigation' : 'Main navigation (collapsed)'"
>
    {{-- Sidebar Header --}}
    <div class="hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0 h-[70px]">
        <a href="{{ route('dashboard') }}">
            <img class="default-logo min-h-[22px] max-w-none transition-opacity duration-200"
                :class="expanded ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                src="{{ Vite::image('app/default-logo.svg') }}"
                alt="{{ config('app.name') }}"
            />
            <img class="small-logo min-h-[22px] max-w-none absolute left-6 top-1/2 -translate-y-1/2 transition-opacity duration-200"
                :class="expanded ? 'opacity-0' : 'opacity-100 group-hover:opacity-0'"
                src="{{ Vite::image('app/mini-logo.svg') }}"
                alt="{{ config('app.name') }}"
            />
        </a>

        {{-- Toggle Button --}}
        <button @click="toggle()"
            @keydown.escape="$store.sidebar && ($store.sidebar.expanded = false)"
            :aria-expanded="expanded"
            aria-label="Toggle sidebar navigation"
            class="size-[30px] absolute start-full top-2/4 -translate-x-2/4 -translate-y-2/4
                   inline-flex items-center justify-center
                   rounded-lg border border-border bg-background
                   hover:bg-accent hover:text-accent-foreground
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2
                   cursor-pointer transition-colors"
        >
            <x-heroicon-s-chevron-left class="size-4 text-muted-foreground transition-all duration-300"
               x-bind:class="expanded ? '' : 'rotate-180'" />
        </button>
    </div>

    {{-- Sidebar Content --}}
    <div class="flex grow shrink-0 py-5 pe-2">
        <div class="grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3 overflow-y-auto scrollbar-thin scrollbar-thumb-muted scrollbar-track-transparent hover:scrollbar-thumb-muted-foreground">
            <x-sidebar.menu />
        </div>
    </div>
</aside>

{{-- Mobile Sidebar Drawer --}}
<div x-data
    x-show="$store.sidebar && $store.sidebar.mobileOpen"
    x-cloak
    class="lg:hidden fixed inset-0 z-50"
>
    {{-- Backdrop --}}
    <div x-show="$store.sidebar && $store.sidebar.mobileOpen"
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
    <aside x-show="$store.sidebar && $store.sidebar.mobileOpen"
        x-transition:enter="transition-transform ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        @keydown.escape.window="$store.sidebar && $store.sidebar.closeMobile()"
        class="relative w-[280px] h-full bg-background border-e border-e-border flex flex-col"
    >
        {{-- Mobile Logo Area --}}
        <div class="h-[60px] flex items-center justify-between px-6 shrink-0 border-b border-border">
            <a href="{{ route('dashboard') }}">
                <img class="min-h-[22px] max-w-none"
                    src="{{ Vite::image('app/default-logo.svg') }}"
                    alt="{{ config('app.name') }}"
                />
            </a>

            {{-- Close Button --}}
            <button @click="$store.sidebar && $store.sidebar.closeMobile()"
                aria-label="Close navigation"
                class="inline-flex items-center justify-center size-8
                       rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2
                       cursor-pointer transition-colors"
            >
                <x-heroicon-o-x-mark class="size-5" />
            </button>
        </div>

        {{-- Mobile Menu Area --}}
        <div class="flex grow shrink-0 py-5 pe-2">
            <div class="grow shrink-0 flex ps-5 pe-3 overflow-y-auto">
                <x-sidebar.menu />
            </div>
        </div>
    </aside>
</div>
