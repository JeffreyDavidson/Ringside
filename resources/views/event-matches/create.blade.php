<x-layouts.app>
    <x-slot:toolbar>
        <x-toolbar>
            <x-page-heading>Event Matches</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home"/>
                <x-breadcrumbs.separator/>
                <x-breadcrumbs.item :url="route('events.index')" label="Events"/>
                <x-breadcrumbs.separator/>
                <x-breadcrumbs.item :url="route('events.show', $event)" :label="$event->name"/>
                <x-breadcrumbs.separator/>
                <x-breadcrumbs.item :url="route('events.matches.index', $event)" label="Matches"/>
                <x-breadcrumbs.separator/>
                <x-breadcrumbs.item label="Create"/>
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <livewire:event-matches.match-form :event="$event"/>
</x-layouts.app>
