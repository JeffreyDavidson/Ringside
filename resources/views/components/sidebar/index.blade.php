<nav x-data="{
        expanded: true,
        init() {
            this.$watch('$store.sidebar.expanded', value => this.expanded = value);
            if ($store.sidebar) this.expanded = $store.sidebar.expanded;
        },
        toggle() {
            if ($store.sidebar) $store.sidebar.toggle();
        }
    }"
    x-bindx-bind:class="expanded ? 'w-[--sidebar-default-width]' : 'w-[--sidebar-collapsed-width] lg:hover:w-[--sidebar-default-width]'"
    x-bind:aria-label="expanded ? 'Main navigation' : 'Main navigation (collapsed)'"
    class="bg-light border-e border-e-gray-200 fixed z-20 hidden lg:flex flex-col items-stretch shrink-0 h-full transition-all duration-300">
    <div class="h-[--header-height] hidden items-center relative justify-between px-3 shrink-0 lg:flex lg:px-6">
        <a href="{{ route('dashboard') }}">
            <img class="min-h-[22px] max-w-none" x-bindx-bind:class="expanded ? 'lg:block' : 'hidden'"
                src="{{ Vite::image('app/default-logo.svg') }}" />
            <img class="min-h-[22px] max-w-none" src="{{ Vite::image('app/mini-logo.svg') }}"
                x-bindx-bind:class="expanded ? 'hidden' : 'lg:block'" />
        </a>
        <button @click="toggle()"
            @keydown.escape="expanded = false"
            x-bind:aria-expanded="expanded"
            aria-label="Toggle sidebar navigation"
            class="inline-flex items-center cursor-pointer leading-none ps-1 pe-1 font-medium text-2sm outline-none justify-center p-0 gap-0 size-[30px] rounded-lg border border-gray-200 bg-light text-gray-500 hover:text-gray-700 focus:ring-2 focus:ring-primary focus:ring-offset-2 toggle absolute left-full top-2/4 -translate-x-2/4 -translate-y-2/4">
            <i class="ki-arrow-left text-lg" x-bind:class="expanded ? '' : 'rotate-180'"></i>
        </button>
    </div>

    <div class="flex grow shrink-0 py-5 pr-2" id="sidebar_content">
        <div class="scrollable-y-hover grow shrink-0 flex pl-2 lg:pl-5 pr-1 lg:pr-3">
            <!-- Sidebar Menu -->
            <x-menu class="flex flex-col grow gap-0.5">
                <x-menu.menu-item variant="sidebar">
                    <x-sidebar.menu-label>
                        <x-sidebar.menu-icon icon="home" />
                        <x-sidebar.menu-link x-bind:class="expanded ? 'lg:block' : 'hidden'" href="{{ route('dashboard') }}"
                            :isCurrent="request()->routeIs('dashboard')">Dashboard</x-sidebar.menu-link>
                    </x-sidebar.menu-label>
                </x-menu.menu-item>
                <x-sidebar.menu-heading x-bind:class="expanded ? 'lg:block' : 'hidden'">User</x-sidebar.menu-heading>
                <div x-data="{
                    open: @json(request()->is('roster/*')),
                    toggle() {
                        this.open = !this.open
                    }
                }">
                    <x-sidebar.menu-label @click="toggle">
                        <x-sidebar.menu-icon icon="users" />
                        <x-sidebar.menu-title x-bind:class="expanded ? 'lg:block' : 'hidden'">Roster</x-sidebar.menu-title>
                        <x-sidebar.menu-accordian-icons x-bind:class="expanded ? '' : 'hidden'" />
                    </x-sidebar.menu-label>
                    <x-sidebar.menu-accordian x-show="open">
                        <x-sidebar.accordian-link href="{{ route('wrestlers.index') }}" :isCurrent="request()->routeIs('wrestlers.*')"
                            x-show="open || expanded">Wrestlers</x-sidebar.accordian-link>
                        <x-sidebar.accordian-link href="{{ route('tag-teams.index') }}" :isCurrent="request()->routeIs('tag-teams.*')"
                            x-show="open">
                            Tag Teams</x-sidebar.accordian-link>
                        <x-sidebar.accordian-link href="{{ route('managers.index') }}" :isCurrent="request()->routeIs('managers.*')"
                            x-show="open">Managers</x-sidebar.accordian-link>
                        <x-sidebar.accordian-link href="{{ route('referees.index') }}" :isCurrent="request()->routeIs('referees.*')"
                            x-show="open">Referees</x-sidebar.accordian-link>
                        <x-sidebar.accordian-link href="{{ route('stables.index') }}" :isCurrent="request()->routeIs('stables.*')"
                            x-show="open">Stables</x-sidebar.accordian-link>
                    </x-sidebar.menu-accordian>
                </div>
                <x-menu.menu-item variant="sidebar">
                    <x-sidebar.menu-label>
                        <x-sidebar.menu-icon icon="trophy" />
                        <x-sidebar.menu-link x-bind:class="expanded ? 'lg:block' : 'hidden'" :href="route('titles.index')"
                            :isCurrent="request()->routeIs('titles.*')">Titles</x-sidebar.menu-link>
                    </x-sidebar.menu-label>
                </x-menu.menu-item>
                <x-menu.menu-item variant="sidebar">
                    <x-sidebar.menu-label>
                        <x-sidebar.menu-icon icon="building-office" />
                        <x-sidebar.menu-link x-bind:class="expanded ? 'lg:block' : 'hidden'" :href="route('venues.index')"
                            :isCurrent="request()->routeIs('venues.*')">Venues</x-sidebar.menu-link>
                    </x-sidebar.menu-label>
                </x-menu.menu-item>
                <x-menu.menu-item variant="sidebar">
                    <x-sidebar.menu-label>
                        <x-sidebar.menu-icon icon="calendar-days" />
                        <x-sidebar.menu-link x-bind:class="expanded ? 'lg:block' : 'hidden'" :href="route('events.index')"
                            :isCurrent="request()->routeIs('events.*')">Events</x-sidebar.menu-link>
                    </x-sidebar.menu-label>
                </x-menu.menu-item>
                <x-sidebar.menu-heading x-bind:class="expanded ? 'lg:block' : 'hidden'">System</x-sidebar.menu-heading>
                <div x-data="{
                    open: @json(request()->is('user-management/*')),
                    toggle() {
                        this.open = !this.open
                    }
                }">
                    <x-sidebar.menu-label @click="toggle">
                        <x-sidebar.menu-icon icon="users" />
                        <x-sidebar.menu-title x-bind:class="expanded ? 'lg:block' : 'hidden'">User Management</x-sidebar.menu-title>
                        <x-sidebar.menu-accordian-icons x-bind:class="expanded ? '' : 'hidden'" />
                    </x-sidebar.menu-label>
                    <x-sidebar.menu-accordian x-show="open">
                        <x-sidebar.accordian-link href="{{ route('users.index') }}" :isCurrent="request()->routeIs('users.*')"
                            x-show="open || expanded">Users</x-sidebar.accordian-link>
                    </x-sidebar.menu-accordian>
                </div>
            </x-menu>
            <!-- End of Sidebar Menu -->
        </div>
    </div>
</nav>
