<nav class="mt-7 flex grow flex-col gap-1" aria-label="Promotion navigation">
    <x-sidebar.menu-heading>Promotion</x-sidebar.menu-heading>

    <x-sidebar.menu-item href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
        <x-slot:icon>
            <x-heroicon-o-squares-2x2 class="size-5" />
        </x-slot:icon>
        Overview
    </x-sidebar.menu-item>
    <x-sidebar.menu-item href="{{ route('events.index') }}" :active="request()->routeIs('events.*')">
        <x-slot:icon>
            <x-heroicon-o-calendar-days class="size-5" />
        </x-slot:icon>
        Events
    </x-sidebar.menu-item>
    <x-sidebar.menu-item href="{{ route('wrestlers.index') }}" :active="request()->routeIs('wrestlers.*')">
        <x-slot:icon>
            <x-heroicon-o-user-group class="size-5" />
        </x-slot:icon>
        Wrestlers
    </x-sidebar.menu-item>
    <x-sidebar.menu-item href="{{ route('tag-teams.index') }}" :active="request()->routeIs('tag-teams.*')">
        <x-slot:icon>
            <x-heroicon-o-users class="size-5" />
        </x-slot:icon>
        Tag teams
    </x-sidebar.menu-item>
    <x-sidebar.menu-item href="{{ route('managers.index') }}" :active="request()->routeIs('managers.*')">
        <x-slot:icon>
            <x-heroicon-o-briefcase class="size-5" />
        </x-slot:icon>
        Managers
    </x-sidebar.menu-item>
    <x-sidebar.menu-item href="{{ route('referees.index') }}" :active="request()->routeIs('referees.*')">
        <x-slot:icon>
            <x-heroicon-o-hand-raised class="size-5" />
        </x-slot:icon>
        Referees
    </x-sidebar.menu-item>
    <x-sidebar.menu-item href="{{ route('stables.index') }}" :active="request()->routeIs('stables.*')">
        <x-slot:icon>
            <x-heroicon-o-shield-check class="size-5" />
        </x-slot:icon>
        Stables
    </x-sidebar.menu-item>
    <x-sidebar.menu-item href="{{ route('titles.index') }}" :active="request()->routeIs('titles.*')">
        <x-slot:icon>
            <x-heroicon-o-trophy class="size-5" />
        </x-slot:icon>
        Titles
    </x-sidebar.menu-item>

    <x-sidebar.menu-heading>Shared directory</x-sidebar.menu-heading>
    <x-sidebar.menu-item href="{{ route('venues.index') }}" :active="request()->routeIs('venues.*')">
        <x-slot:icon>
            <x-heroicon-o-building-office class="size-5" />
        </x-slot:icon>
        Venues
    </x-sidebar.menu-item>

    @if (auth()->user()?->role->isAdministrator())
        <x-sidebar.menu-heading>Platform</x-sidebar.menu-heading>
        <x-sidebar.menu-item href="{{ route('promotions.index') }}" :active="request()->routeIs('promotions.*')">
            <x-slot:icon>
                <x-heroicon-o-building-office-2 class="size-5" />
            </x-slot:icon>
            Promotions
        </x-sidebar.menu-item>
    @endif
</nav>
