<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Create Manager Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('managers.store')" id="createManagerForm">
                    @include('managers.partials.form')
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="createManagerForm" />
                <x-form.buttons.submit form="createManagerForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
