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

    $searchSections = collect([
        ['section' => 'overview', 'route' => 'dashboard', 'group' => 'promotion'],
        ['section' => 'events', 'route' => 'events.index', 'group' => 'promotion'],
        ['section' => 'wrestlers', 'route' => 'wrestlers.index', 'group' => 'promotion'],
        ['section' => 'tag_teams', 'route' => 'tag-teams.index', 'group' => 'promotion'],
        ['section' => 'managers', 'route' => 'managers.index', 'group' => 'promotion'],
        ['section' => 'referees', 'route' => 'referees.index', 'group' => 'promotion'],
        ['section' => 'stables', 'route' => 'stables.index', 'group' => 'promotion'],
        ['section' => 'titles', 'route' => 'titles.index', 'group' => 'promotion'],
        ['section' => 'venues', 'route' => 'venues.index', 'group' => 'shared_directory'],
    ])
        ->when(auth()->user()?->role->isAdministrator(), fn ($sections) => $sections->push(
            ['section' => 'promotions', 'route' => 'promotions.index', 'group' => 'platform'],
        ))
        ->when(auth()->user()?->can('viewAny', \App\Models\Users\User::class), fn ($sections) => $sections->push(
            ['section' => 'user_management', 'route' => 'users.index', 'group' => 'platform'],
        ));
@endphp

<header
    x-data="{
        searchOpen: false,
        query: '',
        shortcutLabel: /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘ K' : 'Ctrl K',
        openSearch() {
            this.searchOpen = true;
            this.$nextTick(() => this.$refs.search.focus());
        },
        closeSearch(returnFocus = false) {
            if (! this.searchOpen) return;

            this.searchOpen = false;
            this.query = '';

            if (returnFocus) this.$nextTick(() => this.$refs.searchToggle.focus());
        },
        matches(section) {
            return section.toLocaleLowerCase().includes(this.query.trim().toLocaleLowerCase());
        },
        matchingLinks() {
            return [...this.$refs.searchResults.querySelectorAll('a[data-section]')].filter((link) =>
                this.matches(link.dataset.section),
            );
        },
        openFirstMatch() {
            this.matchingLinks()[0]?.click();
        },
    }"
    @keydown.window="
        if (($event.metaKey || $event.ctrlKey) && $event.key.toLowerCase() === 'k') {
            $event.preventDefault();
            searchOpen ? closeSearch(true) : openSearch();
        }
    "
    @keydown.escape.window="closeSearch(true)"
    class="border-ringside-line bg-ringside-surface-header fixed inset-x-0 top-0 z-40 flex h-[var(--header-height)] min-h-[var(--header-height)] min-w-0 items-center border-b transition-[inset-inline-start] duration-[var(--sidebar-transition-duration)] ease-[var(--sidebar-transition-timing)] lg:start-[var(--shell-sidebar-width)] lg:end-0"
    data-test="app-shell-header"
>
    <div class="flex w-full min-w-0 items-center gap-4 px-4 lg:px-7">
        <button @click="$store.sidebar && $store.sidebar.openMobile($el)"
            type="button"
            aria-label="Open navigation"
            aria-controls="app-sidebar"
            aria-expanded="false"
            :aria-expanded="$store.sidebar && $store.sidebar.mobileOpen ? 'true' : 'false'"
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
                type="button"
                x-ref="searchToggle"
                @click="searchOpen ? closeSearch() : openSearch()"
                :aria-expanded="searchOpen"
                aria-expanded="false"
                aria-haspopup="dialog"
                aria-controls="shell-search-panel"
                aria-keyshortcuts="Control+K Meta+K"
                aria-label="{{ __('navigation.search.toggle_label') }}"
                class="border-ringside-line text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink focus-visible:outline-ringside-ink inline-flex min-h-11 items-center gap-2 border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
            >
                <x-heroicon-o-magnifying-glass class="size-4" aria-hidden="true" /><span
                    class="hidden sm:inline"
                    >{{ __('navigation.search.open') }}</span
                ><kbd class="text-ringside-muted hidden text-xs sm:inline" aria-hidden="true" x-text="shortcutLabel"
                    >⌘ K</kbd>
            </button>
            <div
                x-cloak
                x-show="searchOpen"
                x-transition.origin.top.right
                @click.outside="closeSearch()"
                id="shell-search-panel"
                class="border-ringside-line bg-ringside-surface absolute end-0 top-[calc(100%+8px)] z-40 w-[min(24rem,calc(100vw-2rem))] border p-2 shadow-xl motion-reduce:transition-none"
                role="dialog"
                aria-label="{{ __('navigation.search.toggle_label') }}"
            >
                <label class="sr-only" for="shell-search">{{ __('navigation.search.field_label') }}</label>
                <div
                    class="border-ringside-line focus-within:outline-ringside-ink flex items-center gap-2 border-b px-3 focus-within:outline-2 focus-within:outline-offset-2"
                    data-test="header-search-field"
                >
                    <x-heroicon-o-magnifying-glass class="text-ringside-muted size-4" aria-hidden="true" /><input
                        x-ref="search"
                        x-model="query"
                        @keydown.enter.prevent="openFirstMatch()"
                        id="shell-search"
                        type="search"
                        autocomplete="off"
                        placeholder="{{ __('navigation.search.placeholder') }}"
                        class="placeholder:text-ringside-muted min-h-12 w-full bg-transparent text-sm outline-none"
                    />
                </div>
                <div class="py-2" x-ref="searchResults" data-test="header-search-results">
                    @foreach ($searchSections as $searchSection)
                        <a
                            href="{{ route($searchSection['route']) }}"
                            data-section="{{ __("navigation.sections.{$searchSection['section']}") }}"
                            x-show="matches($el.dataset.section)"
                            @click="closeSearch()"
                            class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 items-center px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-[-2px]"
                            >{{ __("navigation.sections.{$searchSection['section']}") }}<span
                                class="text-ringside-muted ms-auto text-xs"
                                >{{ __("navigation.groups.{$searchSection['group']}") }}</span
                            ></a>
                    @endforeach
                    <div role="status">
                        <p x-show="matchingLinks().length === 0" x-cloak class="text-ringside-muted px-3 py-3 text-sm">
                            {{ __('navigation.search.no_results') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
