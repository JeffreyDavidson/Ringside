@php
    $activePromotion = $promotionSwitcherPromotions->firstWhere('id', $activePromotionId);
    $promotionName = $activePromotion?->name ?? 'Ringside';

    $pageLabel = match (true) {
        request()->routeIs('dashboard') => 'Overview',
        request()->routeIs('promotions.*') => 'Promotions',
        request()->routeIs('events.*') => 'Events',
        request()->routeIs('wrestlers.*') => 'Wrestlers',
        request()->routeIs('tag-teams.*') => 'Tag teams',
        request()->routeIs('managers.*') => 'Managers',
        request()->routeIs('referees.*') => 'Referees',
        request()->routeIs('stables.*') => 'Stables',
        request()->routeIs('titles.*') => 'Titles',
        request()->routeIs('venues.*') => 'Venues',
        request()->routeIs('users.*') => 'User management',
        default => 'Ringside',
    };

    $workspaceLabel = match (true) {
        $pageLabel === 'Venues' => 'Shared directory',
        $pageLabel === 'Promotions' => 'Platform',
        default => $promotionName,
    };
@endphp

<header
    x-data="{ searchOpen: false }"
    @keydown.escape.window="searchOpen = false"
    class="border-ringside-line bg-ringside-surface-header fixed inset-x-0 top-0 z-40 flex h-[var(--header-height)] min-h-[var(--header-height)] min-w-0 items-center border-b transition-[inset-inline-start] duration-[var(--sidebar-transition-duration)] ease-in-out lg:start-[var(--shell-header-start)] lg:end-0"
    :style="$store.sidebar && $store.sidebar.expanded
        ? '--shell-header-start: var(--sidebar-default-width)'
        : '--shell-header-start: var(--sidebar-collapsed-width)'"
>
    <div class="flex w-full min-w-0 items-center gap-4 px-4 lg:px-7">
        <button @click="$store.sidebar && $store.sidebar.openMobile()"
        aria-label="Open navigation"
        class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink inline-flex size-11 items-center justify-center lg:hidden"
    >
        <x-heroicon-o-bars-3 class="size-5" />
    </button>
    <nav class="flex min-w-0 items-center gap-2 text-sm" aria-label="Breadcrumb">
        <span class="bg-ringside-signal hidden size-1.5 shrink-0 sm:block" aria-hidden="true"></span>
        <span
            class="text-ringside-muted truncate text-xs font-semibold tracking-[0.08em] uppercase"
        >{{ $workspaceLabel }}</span>
            <x-heroicon-o-chevron-right class="text-ringside-muted hidden size-3.5 shrink-0 sm:block" />
            <strong class="text-ringside-ink truncate font-semibold">{{ $pageLabel }}</strong>
        </nav>
        <div class="relative ms-auto">
            <button
                @click="searchOpen = ! searchOpen"
                :aria-expanded="searchOpen"
                aria-haspopup="dialog"
                aria-label="Search navigation"
                class="border-ringside-line text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink inline-flex min-h-11 items-center gap-2 border px-3 text-sm"
            >
                <x-heroicon-o-magnifying-glass class="size-4" /><span class="hidden sm:inline">Search</span
                ><kbd class="text-ringside-muted hidden text-xs sm:inline">⌘ K</kbd>
            </button>
            <div
                x-cloak
                x-show="searchOpen"
                x-transition.origin.top.right
                @click.outside="searchOpen = false"
                class="border-ringside-line bg-ringside-surface absolute end-0 top-[calc(100%+8px)] z-40 w-[min(24rem,calc(100vw-2rem))] border p-2 shadow-xl"
                role="dialog"
                aria-label="Search navigation"
            >
                <label class="sr-only" for="shell-search">Find a section</label>
                <div class="border-ringside-line flex items-center gap-2 border-b px-3">
                    <x-heroicon-o-magnifying-glass class="text-ringside-muted size-4" /><input
                        x-ref="search"
                        id="shell-search"
                        type="search"
                        placeholder="Find a section…"
                        class="placeholder:text-ringside-muted min-h-12 w-full bg-transparent text-sm outline-none"
                    />
                </div>
                <div class="py-2">
                    <a
                        href="{{ route('dashboard') }}"
                        @click="searchOpen = false"
                        class="hover:bg-ringside-surface-hover flex min-h-11 items-center px-3 text-sm"
                    >Overview<span class="text-ringside-muted ms-auto text-xs">Promotion</span></a>
                    <a
                        href="{{ route('events.index') }}"
                        @click="searchOpen = false"
                        class="hover:bg-ringside-surface-hover flex min-h-11 items-center px-3 text-sm"
                    >Events<span class="text-ringside-muted ms-auto text-xs">Promotion</span></a>
                    <a
                        href="{{ route('wrestlers.index') }}"
                        @click="searchOpen = false"
                        class="hover:bg-ringside-surface-hover flex min-h-11 items-center px-3 text-sm"
                    >Wrestlers<span class="text-ringside-muted ms-auto text-xs">Promotion</span></a>
                    <a
                        href="{{ route('venues.index') }}"
                        @click="searchOpen = false"
                        class="hover:bg-ringside-surface-hover flex min-h-11 items-center px-3 text-sm"
                    >Venues<span class="text-ringside-muted ms-auto text-xs">Shared directory</span></a>
                </div>
            </div>
        </div>
    </div>
</header>
