<x-layouts.app>
    <x-slot:toolbar>
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
        <x-card.header>
            <x-card.title class="m-0">
                <x-card.heading>Edit Title Form</x-card.heading>
            </x-card.title>
        </x-card.header>
        <x-card.body>
            <x-form :action="route('titles.update', $title)" id="editTitleForm">
                @method('PATCH')
                @include('titles.partials.form')
            </x-form>
        </x-card.body>
        <x-card.footer>
            <x-form.buttons.reset form="editTitleForm"/>
            <x-form.buttons.submit form="editTitleForm"/>
        </x-card.footer>
    </x-card>
</x-layouts.app>
