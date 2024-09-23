<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Create Title Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('titles.store')" id="createTitleForm">
                    @include('titles.partials.form')
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="createTitleForm" />
                <x-form.buttons.submit form="createTitleForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
