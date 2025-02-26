<div x-data="{ expanded: true }"
    :class="expanded ? 'w-[--sidebar-default-width]' : 'w-[--sidebar-collapsed-width] lg:hover:w-[--sidebar-default-width]'"
    class="w-[--sidebar-width] bg-light border-e border-e-gray-200 fixed z-20 hidden lg:flex flex-col items-stretch shrink-0 h-full">
    <div class="h-[--header-height] hidden items-center relative justify-between px-3 shrink-0 lg:flex lg:px-6">
        <a href="{{ route('dashboard') }}">
            <img class="min-h-[22px] max-w-none" src="{{ Vite::image('app/default-logo.svg') }}"
                :class="expanded ? 'lg:block' : 'hidden'" />
            <img class="min-h-[22px] max-w-none" src="{{ Vite::image('app/mini-logo.svg') }}"
                :class="expanded ? 'hidden' : 'lg:block'" />
        </a>
        <button @click="expanded = !expanded"
            class="inline-flex items-center cursor-pointer leading-none ps-1 pe-1 font-medium text-2sm outline-none justify-center p-0 gap-0 btn-icon-md size-[30px] rounded-lg border border-gray-200 bg-light text-gray-500 hover:text-gray-700 toggle absolute left-full top-2/4 -translate-x-2/4 -translate-y-2/4">
            <i :class="expanded ? '' : 'rotate-180'"
                class="ki-filled ki-black-left-line text-[.9375rem] toggle-active:rotate-180 transition-all duration-300"></i>
        </button>
    </div>

    <div class="flex grow shrink-0 py-5 pr-2" id="sidebar_content">
        <div class="scrollable-y-hover grow shrink-0 flex pl-2 lg:pl-5 pr-1 lg:pr-3">
            <!-- Sidebar Menu -->
            <x-menu class="flex flex-col grow gap-0.5">
                <x-sidebar.menu-item>
                    <x-sidebar.menu-label>
                        <x-sidebar.menu-icon icon="ki-home" />
                        <x-sidebar.menu-link ::class="expanded ? 'lg:block' : 'hidden'" href="{{ route('dashboard') }}"
                            :isCurrent="request()->routeIs('dashboard')">Dashboard</x-sidebar.menu-link>
                    </x-sidebar.menu-label>
                </x-sidebar.menu-item>
                <x-sidebar.menu-heading ::class="expanded ? 'lg:block' :
                    'hidden relative before:content-['...
                    '] before:absolute before:text-current before:font-before:visible before:inline-block before:bottom-2/4 before:start-0 before:ms-[.225rem] before:translate-x-full'">User</x-sidebar.menu-heading>
                <div x-data="{
                    open: @json(request()->is('roster/*')),
                    toggle() {
                        this.open = !this.open
                    }
                }">
                    <x-sidebar.menu-label @click="toggle">
                        <x-sidebar.menu-icon icon="ki-people" />
                        <x-sidebar.menu-title ::class="expanded ? 'lg:block' : 'hidden'">Roster</x-sidebar.menu-title>
                        <x-sidebar.menu-accordian-icons ::class="expanded ? '' : 'hidden'" />
                    </x-sidebar.menu-label>
                    <x-sidebar.menu-accordian x-show="open">
                        <x-sidebar.accordian-link href="{{ route('wrestlers.index') }}"
                            :isCurrent="request()->routeIs('wrestlers.*')">Wrestlers</x-sidebar.accordian-link>
                        <x-sidebar.accordian-link href="{{ route('tag-teams.index') }}" :isCurrent="request()->routeIs('tag-teams.*')">
                            Tag Teams</x-sidebar.accordian-link>
                        <x-sidebar.accordian-link href="{{ route('managers.index') }}"
                            :isCurrent="request()->routeIs('managers.*')">Managers</x-sidebar.accordian-link>
                        <x-sidebar.accordian-link href="{{ route('referees.index') }}"
                            :isCurrent="request()->routeIs('referees.*')">Referees</x-sidebar.accordian-link>
                        <x-sidebar.accordian-link href="{{ route('stables.index') }}"
                            :isCurrent="request()->routeIs('stables.*')">Stables</x-sidebar.accordian-link>
                    </x-sidebar.menu-accordian>
                </div>
                <x-sidebar.menu-item>
                    <x-sidebar.menu-label>
                        <x-sidebar.menu-icon icon="ki-cup" />
                        <x-sidebar.menu-link ::class="expanded ? 'lg:block' : 'hidden'" :href="route('titles.index')"
                            :isCurrent="request()->routeIs('titles.*')">Titles</x-sidebar.menu-link>
                    </x-sidebar.menu-label>
                </x-sidebar.menu-item>
                <x-sidebar.menu-item>
                    <x-sidebar.menu-label>
                        <x-sidebar.menu-icon icon="ki-home-3" />
                        <x-sidebar.menu-link ::class="expanded ? 'lg:block' : 'hidden'" :href="route('venues.index')"
                            :isCurrent="request()->routeIs('venues.*')">Venues</x-sidebar.menu-link>
                    </x-sidebar.menu-label>
                </x-sidebar.menu-item>
                <x-sidebar.menu-item>
                    <x-sidebar.menu-label>
                        <x-sidebar.menu-icon icon="ki-calendar" />
                        <x-sidebar.menu-link ::class="expanded ? 'lg:block' : 'hidden'" :href="route('events.index')"
                            :isCurrent="request()->routeIs('events.*')">Events</x-sidebar.menu-link>
                    </x-sidebar.menu-label>
                </x-sidebar.menu-item>
                <x-sidebar.menu-item>
                    <x-sidebar.menu-label>
                        <x-sidebar.menu-icon icon="ki-user" />
                        <x-sidebar.menu-link ::class="expanded ? 'lg:block' : 'hidden'" :href="route('users.index')"
                            :isCurrent="request()->routeIs('users.*')">Users</x-sidebar.menu-link>
                    </x-sidebar.menu-label>
                </x-sidebar.menu-item>
                <x-sidebar.menu-heading ::class="expanded ? 'lg:block' :
                    'hidden relative before:content-['...
                    '] before:absolute before:text-current before:font-before:visible before:inline-block before:bottom-2/4 before:start-0 before:ms-[.225rem] before:translate-x-full'">Docs</x-sidebar.menu-heading>
                <x-sidebar.menu-item>
                    <x-sidebar.menu-label>
                        <x-sidebar.menu-icon icon="ki-cup" />
                        <x-sidebar.menu-link ::class="expanded ? 'lg:block' : 'hidden'" :href="route('docs.buttons')"
                            :isCurrent="request()->routeIs('docs.buttons')">Buttons</x-sidebar.menu-link>
                    </x-sidebar.menu-label>
                </x-sidebar.menu-item>
            </x-menu>
            <!-- End of Sidebar Menu -->
        </div>
    </div>
</div>
