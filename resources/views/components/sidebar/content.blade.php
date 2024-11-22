<div class="sidebar-content flex grow shrink-0 py-5 pr-2" id="sidebar_content">
    <div class="scrollable-y-hover grow shrink-0 flex pl-2 lg:pl-5 pr-1 lg:pr-3">
        <!-- Sidebar Menu -->
        <x-menu class="flex flex-col grow gap-0.5">
            <x-sidebar.menu-item>
                <x-sidebar.menu-label>
                    <x-sidebar.menu-icon icon="ki-home" />
                    <x-sidebar.menu-title :href="route('dashboard')">Dashboard</x-sidebar.menu-title>
                </x-sidebar.menu-label>
            </x-sidebar.menu-item>
            <x-sidebar.menu-heading>User</x-sidebar.menu-heading>
            <x-sidebar.menu-item
                x-data="{
                    open: false,
                    toggle() {
                        if (this.open) {
                            return this.close()
                        }

                        this.$refs.button.focus()

                        this.open = true
                    },
                    close(focusAfter) {
                        if (! this.open) return

                        this.open = false

                        focusAfter && focusAfter.focus()
                    }}">
                <x-sidebar.menu-label hasSub isOpen=open>
                    <x-sidebar.menu-icon icon="ki-people" />
                    <x-sidebar.menu-title :href="route('dashboard')">Roster</x-sidebar.menu-title>
                </x-sidebar.menu-label>
                <x-slot:subMenu isOpen>
                    <x-sidebar.menu-link :href="route('wrestlers.index')">Wrestlers</x-sidebar.menu-link>
                    <x-sidebar.menu-link :href="route('tag-teams.index')">Tag Teams</x-sidebar.menu-link>
                    <x-sidebar.menu-link :href="route('referees.index')">Referees</x-sidebar.menu-link>
                    <x-sidebar.menu-link :href="route('managers.index')">Managers</x-sidebar.menu-link>
                    <x-sidebar.menu-link :href="route('stables.index')">Stables</x-sidebar.menu-link>
                </x-slot:subMenu>
            </x-sidebar.menu-item>
            <x-sidebar.menu-item>
                <x-sidebar.menu-label>
                    <x-sidebar.menu-icon icon="ki-cup" />
                    <x-sidebar.menu-title :href="route('titles.index')">Titles</x-sidebar.menu-title>
                </x-sidebar.menu-label>
            </x-sidebar.menu-item>
            <x-sidebar.menu-item>
                <x-sidebar.menu-label>
                    <x-sidebar.menu-icon icon="ki-home-3" />
                    <x-sidebar.menu-title :href="route('venues.index')">Venues</x-sidebar.menu-title>
                </x-sidebar.menu-label>
            </x-sidebar.menu-item>
            <x-sidebar.menu-item>
                <x-sidebar.menu-label>
                    <x-sidebar.menu-icon icon="ki-calendar" />
                    <x-sidebar.menu-title :href="route('events.index')">Events</x-sidebar.menu-title>
                </x-sidebar.menu-label>
            </x-sidebar.menu-item>
        </x-menu>
        <!-- End of Sidebar Menu -->
    </div>
</div>
