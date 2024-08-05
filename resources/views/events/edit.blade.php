<x-layouts.app>
    <x-slot:toolbar>
        <x-toolbar>
            <x-page-heading>Edit Event</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('events.index')" label="Events" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('events.show', $event)" :label="$event->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Edit" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-card.header>
            <x-card.title class="m-0">
                <x-card.heading>Edit Event Form</x-card.heading>
            </x-card.title>
        </x-card.header>
        <x-card.body>
            <x-form :action="route('events.store')" id="editEventForm">
                @method('PATCH')
                @include('events.partials.form')
            </x-form>
        </x-card.body>
        <x-card.footer>
            <x-form.buttons.reset form="editEventForm"/>
            <x-form.buttons.submit form="editEventForm"/>
        </x-card.footer>
    </x-card>
</x-layouts.app>
