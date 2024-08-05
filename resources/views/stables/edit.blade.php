<x-layouts.app>
    <x-slot:toolbar>
        <x-toolbar>
            <x-page-heading>Edit Stable</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('stables.index')" label="Stables" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('stables.show', $stable)" :label="$stable->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Edit" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-card.header>
            <x-card.title class="m-0">
                <x-card.heading>Edit Stable Form</x-card.heading>
            </x-card.title>
        </x-card.header>
        <x-card.body>
            <x-form :action="route('stables.update', $stable)" id="editStableForm">
                @include('stables.partials.form')
            </x-form>
        </x-card.body>
        <x-card.footer>
            <x-form.buttons.reset form="editStableForm"/>
            <x-form.buttons.submit form="editStableForm"/>
        </x-card.footer>
    </x-card>
</x-layouts.app>
