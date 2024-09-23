<x-layouts.app>
    <x-container-fixed>
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
                <x-form.buttons.reset form="editTitleForm" />
                <x-form.buttons.submit form="editTitleForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
