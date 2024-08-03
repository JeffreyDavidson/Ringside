<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Edit Title</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('titles.index')" label="Titles" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('titles.show', $title)" :label="$title->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Edit" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-slot name="header">
            <x-card.header title="Edit Title Form" />
        </x-slot>
        <x-card.body>
            <x-form :action="route('titles.update', $title)" id="editTitleForm">
                @method('PATCH')
                @include('titles.partials.form')
            </x-form>
        </x-card.body>
        <x-slot name="footer">
            <x-card.footer>
                <x-form.buttons.reset form="editTitleForm"/>
                <x-form.buttons.submit form="editTitleForm"/>
            </x-card.footer>
        </x-slot>
    </x-card>
</x-layouts.app>
