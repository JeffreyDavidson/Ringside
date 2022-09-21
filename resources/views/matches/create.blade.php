<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="Event Matches">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('events.index')" label="Events" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('events.show', $event)" :label="$event->name" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('events.matches.index', $event)" label="Matches" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item label="Create" />
        </x-toolbar>
    </x-slot>

    <livewire:event-matches.match-create-form>

</x-layouts.app>
