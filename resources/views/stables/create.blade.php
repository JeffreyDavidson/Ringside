<x-layouts.app>
    <x-slot:toolbar>
        <x-toolbar>
            <x-page-heading>Create Stable</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('stables.index')" label="Stables" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Create" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-card.header>
            <x-card.title class="m-0">
                <x-card.heading>Create Stable Form</x-card.heading>
            </x-card.title>
        </x-card.header>
        <x-card.body>
            <x-form :action="route('stables.store')" id="createStableForm">
                @include('stables.partials.form')
            </x-form>
        </x-card.body>
        <x-card.footer>
            <x-form.buttons.reset form="createStableForm"/>
            <x-form.buttons.submit form="createStableForm"/>
        </x-card.footer>
    </x-card>
</x-layouts.app>
