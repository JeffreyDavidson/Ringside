<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Edit Event Match</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('events.index')" label="Events" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('events.show', $event)" :label="$event->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('events.matches.index', $event)" label="Matches" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('events.matches.edit', [$event, $match])" :label="$match->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Edit" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-slot name="header">
            <x-card.header title="Edit Match Form" />
        </x-slot>
        <x-card.body>
            <livewire:event-matches.match-form :event="$event" :match="$match" />
        </x-card.body>
    </x-card>
</x-layouts.app>
