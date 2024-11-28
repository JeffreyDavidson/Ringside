<x-mega-menu.container>
    <x-mega-menu.inner>
        <x-mega-menu.wrapper>
            <x-mega-menu.menu>
                <x-mega-menu.menu-item>
                    <x-mega-menu.menu-link :isActive="Route::is('dashboard')" href="{{ route('dashboard') }}">
                        Home
                    </x-mega-menu.menu-link>
                </x-mega-menu.menu-item>
                <x-mega-menu.menu-item class="group">
                    <x-mega-menu.menu-link>Roster</x-mega-menu.menu-link>
                    <x-mega-menu.menu-dropdown>
                        <x-menu.menu-item>
                            <div class="flex py-0 flex-col">
                                <x-mega-menu.dropdown.menu-item>
                                    <x-mega-menu.dropdown.menu-link href="{{ route('wrestlers.index') }}">
                                        Wrestlers
                                    </x-mega-menu.dropdown.menu-link>
                                </x-mega-menu.dropdown.menu-item>
                                <x-mega-menu.dropdown.menu-item>
                                    <x-mega-menu.dropdown.menu-link href="{{ route('tag-teams.index') }}">
                                        Tag Teams
                                    </x-mega-menu.dropdown.menu-link>
                                </x-mega-menu.dropdown.menu-item>
                                <x-mega-menu.dropdown.menu-item>
                                    <x-mega-menu.dropdown.menu-link href="{{ route('referees.index') }}">
                                        Referees
                                    </x-mega-menu.dropdown.menu-link>
                                </x-mega-menu.dropdown.menu-item>
                                <x-mega-menu.dropdown.menu-item>
                                    <x-mega-menu.dropdown.menu-link href="{{ route('managers.index') }}">
                                        Managers
                                    </x-mega-menu.dropdown.menu-link>
                                </x-mega-menu.dropdown.menu-item>
                                <x-mega-menu.dropdown.menu-item>
                                    <x-mega-menu.dropdown.menu-link href="{{ route('stables.index') }}">
                                        Stables
                                    </x-mega-menu.dropdown.menu-link>
                                </x-mega-menu.dropdown.menu-item>
                            </div>
                        </x-menu.menu-item>
                    </x-mega-menu.menu-dropdown>
                </x-mega-menu.menu-item>
                <x-mega-menu.menu-item>
                    <x-mega-menu.menu-link isActive="{{ \Route::is('titles.index') }}" href="{{ route('titles.index') }}">
                        Titles
                    </x-mega-menu.menu-link>
                </x-mega-menu.menu-item>
                <x-mega-menu.menu-item>
                    <x-mega-menu.menu-link isActive="{{ \Route::is('venues.index') }}"
                        href="{{ route('venues.index') }}">
                        Venues
                    </x-mega-menu.menu-link>
                </x-mega-menu.menu-item>
                <x-mega-menu.menu-item>
                    <x-mega-menu.menu-link isActive="{{ \Route::is('events.index') }}"
                        href="{{ route('events.index') }}">
                        Events
                    </x-mega-menu.menu-link>
                </x-mega-menu.menu-item>
            </x-mega-menu.menu>
        </x-mega-menu.wrapper>
    </x-mega-menu.inner>
</x-mega-menu.container>
