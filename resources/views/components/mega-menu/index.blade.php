<x-mega-menu.container>
    <div class="flex items-stretch">
        <x-mega-menu.wrapper>
            <div class="flex flex-col lg:flex-row gap-5 lg:gap-7.5">
                <x-mega-menu.menu-item>
                    <x-mega-menu.menu-link :isActive="Route::is('dashboard')" href="{{ route('dashboard') }}">Home</x-mega-menu.menu-link>
                </x-mega-menu.menu-item>
                <x-mega-menu.menu-item>
                    <x-mega-menu.menu-label>Roster</x-mega-menu.menu-label>
                    <x-mega-menu.menu-dropdown>
                        <div
                            class="lg:w-[250px] mt-2 lg:mt-0 lg:border-r lg:border-r-gray-200 rounded-xl lg:rounded-l-xl lg:rounded-r-none shrink-0 px-3 py-4 lg:p-7.5 bg-light-active">
                            <x-mega-menu.dropdown.heading>Roster</x-mega-menu.dropdown.heading>
                            <div class="flex py-0 flex-col">
                                <x-mega-menu.dropdown.menu-item>
                                    <x-mega-menu.dropdown.menu-link
                                        href="{{ route('wrestlers.index') }}">Wrestlers</x-mega-menu.dropdown.menu-link>
                                </x-mega-menu.dropdown.menu-item>
                                <x-mega-menu.dropdown.menu-item>
                                    <x-mega-menu.dropdown.menu-link href="{{ route('tag-teams.index') }}">Tag
                                        Teams</x-mega-menu.dropdown.menu-link>
                                </x-mega-menu.dropdown.menu-item>
                                <x-mega-menu.dropdown.menu-item>
                                    <x-mega-menu.dropdown.menu-link
                                        href="{{ route('referees.index') }}">Referees</x-mega-menu.dropdown.menu-link>
                                </x-mega-menu.dropdown.menu-item>
                                <x-mega-menu.dropdown.menu-item>
                                    <x-mega-menu.dropdown.menu-link
                                        href="{{ route('managers.index') }}">Managers</x-mega-menu.dropdown.menu-link>
                                </x-mega-menu.dropdown.menu-item>
                                <x-mega-menu.dropdown.menu-item>
                                    <x-mega-menu.dropdown.menu-link
                                        href="{{ route('stables.index') }}">Stables</x-mega-menu.dropdown.menu-link>
                                </x-mega-menu.dropdown.menu-item>
                            </div>
                        </div>
                    </x-mega-menu.menu-dropdown>
                </x-mega-menu.menu-item>
                <x-mega-menu.menu-item>
                    <x-mega-menu.menu-link isActive="{{ \Route::is('titles.index') }}"
                        href="{{ route('titles.index') }}">Titles</x-mega-menu.menu-link>
                </x-mega-menu.menu-item>
                <x-mega-menu.menu-item>
                    <x-mega-menu.menu-link isActive="{{ \Route::is('venues.index') }}"
                        href="{{ route('venues.index') }}">Venues</x-mega-menu.menu-link>
                </x-mega-menu.menu-item>
                <x-mega-menu.menu-item>
                    <x-mega-menu.menu-link isActive="{{ \Route::is('events.index') }}"
                        href="{{ route('events.index') }}">Events</x-mega-menu.menu-link>
                </x-mega-menu.menu-item>
            </div>
        </x-mega-menu.wrapper>
    </div>
</x-mega-menu.container>
