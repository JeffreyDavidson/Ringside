<x-layouts.app>
    <x-container-fluid>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Create Title Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('titles.store')" id="createTitleForm">
                    <x-titles.form :$title />
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="createTitleForm" />
                <x-form.buttons.submit form="createTitleForm" />
            </x-card.footer>
        </x-card>
    </x-container-fluid>
</x-layouts.app>
