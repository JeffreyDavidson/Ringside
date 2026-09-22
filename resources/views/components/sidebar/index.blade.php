@php
    $user = Auth::user();
    $activePromotion = $promotionSwitcherPromotions->firstWhere('id', $activePromotionId);
@endphp

<div
    x-data="{
        expanded: $store.sidebar ? $store.sidebar.expanded : true,
        init() {
            if ($store.sidebar) this.$watch('$store.sidebar.expanded', (value) => (this.expanded = value));
        },
        toggle() {
            if ($store.sidebar) $store.sidebar.toggle();
        },
    }"
>
    <div
        x-show="$store.sidebar && $store.sidebar.mobileOpen"
        x-cloak
        @click="$store.sidebar && $store.sidebar.closeMobile()"
        class="fixed inset-0 z-20 bg-black/70 lg:hidden"
        aria-hidden="true"
    ></div>

    <aside
        @mouseenter="$store.sidebar && ($store.sidebar.hovered = true)"
        @mouseleave="$store.sidebar && ($store.sidebar.hovered = false)"
        :class="[
            expanded ? 'lg:w-[var(--sidebar-default-width)]' : 'lg:w-[var(--sidebar-collapsed-width)]',
            $store.sidebar && $store.sidebar.mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]"
        :data-collapsed="! expanded"
        class="group border-ringside-line bg-ringside-surface-header fixed inset-y-0 start-0 z-50 flex w-[var(--sidebar-default-width)] shrink-0 flex-col border-e transition-[width,transform] duration-[var(--sidebar-transition-duration)] ease-in-out"
        :aria-label="expanded ? 'Main navigation' : 'Main navigation (collapsed)'"
    >
        <div class="border-ringside-line relative flex h-[var(--header-height)] min-h-[var(--header-height)] shrink-0 items-center border-b px-6 group-data-[collapsed=true]:px-4">
            <a
                class="font-display text-[2rem] leading-none tracking-tight transition-[transform,left] duration-[var(--sidebar-transition-duration)] ease-in-out group-data-[collapsed=true]:absolute group-data-[collapsed=true]:start-[calc(50%_-_8px)] group-data-[collapsed=true]:-translate-x-1/2"
                href="{{ route('dashboard') }}"
                aria-label="Ringside dashboard"
            >
                <span x-show="expanded">RING<span class="text-ringside-signal">SIDE</span></span>
                <span x-show="! expanded" aria-hidden="true">R<span class="text-ringside-signal">S</span></span>
            </a>
            <button
                @click="toggle()"
                :aria-expanded="expanded"
                :aria-label="expanded ? 'Collapse sidebar' : 'Expand sidebar'"
                class="border-ringside-line bg-ringside-surface-header text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink focus-visible:outline-ringside-ink absolute end-0 top-1/2 hidden size-8 translate-x-1/2 -translate-y-1/2 items-center justify-center border focus-visible:outline-2 focus-visible:outline-offset-4 lg:inline-flex"
            >
                <x-heroicon-s-chevron-left class="sidebar-toggle-icon size-4" />
            </button>
            <button
                @click="$store.sidebar && $store.sidebar.closeMobile()"
                aria-label="Close navigation"
                class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink ms-auto inline-flex size-10 items-center justify-center lg:hidden"
            >
                <x-heroicon-o-x-mark class="size-5" />
            </button>
        </div>

        <div class="flex min-h-0 grow [scrollbar-color:var(--color-ringside-line)_transparent] flex-col overflow-y-auto px-3 py-6">
            @if ($activePromotion)
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = ! open"
                    :aria-expanded="open"
                    aria-controls="promotion-menu"
                    class="border-ringside-line hover:bg-ringside-surface hover:text-ringside-ink focus-visible:outline-ringside-ink flex min-h-[68px] w-full items-center gap-3 border px-3 text-start focus-visible:outline-2 focus-visible:outline-offset-4"
                >
                    <span
                        class="border-ringside-line font-display grid size-9 shrink-0 place-items-center border text-lg leading-none"
                        aria-hidden="true"
                    >{{ str($activePromotion->name)->substr(0, 2)->upper() }}</span>
                        <span x-show="expanded" class="min-w-0 flex-1"
                            ><span class="block truncate text-sm font-semibold">{{ $activePromotion->name }}</span
                            ><span class="text-ringside-muted mt-1 block text-xs">Promotion workspace</span></span>
                        <x-heroicon-o-chevron-down x-show="expanded" class="text-ringside-muted size-4 shrink-0" />
                    </button>
                    <div
                        x-cloak
                        x-show="open"
                        x-transition.origin.top.left
                        @click.outside="open = false"
                        id="promotion-menu"
                        class="border-ringside-line bg-ringside-surface absolute start-0 top-[calc(100%+8px)] z-40 w-60 border p-2 shadow-xl"
                    >
                        <p class="text-ringside-muted px-3 pt-1 pb-2 text-xs">{{ __('promotions.switch') }}</p>
                        @foreach ($promotionSwitcherPromotions as $promotion)
                            <form action="{{ route('promotions.switch') }}" method="post">
                                @csrf
                                <input type="hidden" name="promotion_id" value="{{ $promotion->id }}" />
                                <button
                                    type="submit"
                                    class="hover:bg-ringside-surface-hover flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm"
                                >
                                    <span class="truncate">{{ $promotion->name }}</span>
                                    @if ($promotion->id === $activePromotionId)
                                        <x-heroicon-o-check class="text-ringside-signal ms-auto size-4" />
                                    @endif
                                </button>
                            </form>
                        @endforeach
                        <p class="text-ringside-muted px-3 pt-3 pb-1 text-xs">
                            Memberships determine available workspaces.
                        </p>
                    </div>
                </div>
            @endif

            <x-sidebar.menu />
        </div>

        <div class="shrink-0 px-3 pb-[max(12px,env(safe-area-inset-bottom))] group-data-[collapsed=true]:px-2">
            <a
                href="{{ route('users.index') }}"
                @class(['flex min-h-11 items-center gap-3 px-3 text-ringside-muted transition-[background-color,color,padding] duration-300 ease-out hover:bg-ringside-surface hover:text-ringside-ink group-data-[collapsed=true]:justify-center group-data-[collapsed=true]:gap-0 group-data-[collapsed=true]:px-0', 'bg-ringside-surface-hover text-ringside-ink' => request()->routeIs('users.*')])
            >
                <x-heroicon-o-cog-6-tooth class="size-5 shrink-0" /><span x-show="expanded" class="truncate text-sm"
                    >User management</span>
            </a>
            @if ($user instanceof \App\Models\Users\User)
                <div x-data="{ open: false }" class="border-ringside-line relative mt-4 border-t pt-3">
                    <button @click="open = ! open"
                    :aria-expanded="open"
                    aria-controls="account-menu"
                    data-test="profile-menu"
                    class="hover:bg-ringside-surface hover:text-ringside-ink flex min-h-14 w-full items-center gap-3 px-3 text-start transition-[background-color,color,padding] duration-300 ease-out group-data-[collapsed=true]:justify-center group-data-[collapsed=true]:gap-0 group-data-[collapsed=true]:px-0"
                >
                    <span
                        class="bg-ringside-surface-hover grid size-8 shrink-0 place-items-center text-xs font-semibold"
                    >{{ str($user->full_name)->explode(' ')->filter()->map(fn ($part) => str($part)->substr(0, 1))->join('') }}</span>
                        <span x-show="expanded" class="min-w-0 flex-1"
                            ><span class="block truncate text-sm font-semibold">{{ $user->full_name }}</span
                            ><span
                                class="text-ringside-muted mt-1 block truncate text-xs"
                                >{{ $user->email }}</span
                            ></span>
                        <x-heroicon-o-chevron-up x-show="expanded" class="text-ringside-muted size-4 shrink-0" />
                    </button>
                    <div
                        x-cloak
                        x-show="open"
                        x-transition.origin.bottom.left
                        @click.outside="open = false"
                        id="account-menu"
                        class="border-ringside-line bg-ringside-surface absolute start-0 bottom-[calc(100%+8px)] z-40 w-60 border p-2 shadow-xl"
                    >
                        <p class="text-ringside-muted px-3 pt-1 pb-2 text-xs">Your account</p>
                        <a
                            href="#profile"
                            class="hover:bg-ringside-surface-hover flex min-h-11 items-center gap-3 px-3 text-sm"
                        ><x-heroicon-o-user class="size-4" />Profile</a>
                        <a
                            href="#account-settings"
                            class="hover:bg-ringside-surface-hover flex min-h-11 items-center gap-3 px-3 text-sm"
                        ><x-heroicon-o-cog-6-tooth class="size-4" />Account settings</a>
                        <div class="border-ringside-line my-2 border-t"></div>
                        <form action="{{ route('logout') }}" method="post">
                            @csrf
                            <button
                                type="submit"
                                class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm"
                            >
                                <x-heroicon-o-arrow-left-start-on-rectangle class="size-4" />Log out
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </aside>
</div>
