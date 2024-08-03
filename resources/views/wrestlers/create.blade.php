<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Create Wrestler</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('wrestlers.index')" label="Wrestlers" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Create" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-slot name="header">
            <x-card.header title="Create Wrestler Form" />
        </x-slot>
        <x-card.body>
            <x-form :action="route('wrestlers.store')" id="createWrestlerForm">
                @include('wrestlers.partials.form')
            </x-form>
        </x-card.body>
        <x-slot name="footer">
            <x-card.footer>
                <x-form.buttons.reset form="createWrestlerForm"/>
                <x-form.buttons.submit form="createWrestlerForm"/>
            </x-card.footer>
        </x-slot>
    </x-card>
</x-layouts.app>
