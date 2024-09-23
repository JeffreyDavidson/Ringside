<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Edit Manager Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('managers.update', $manager)" id="editManagerForm">
                    @method('PATCH')
                    @include('managers.partials.form')
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="editManagerForm" />
                <x-form.buttons.submit form="editManagerForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
