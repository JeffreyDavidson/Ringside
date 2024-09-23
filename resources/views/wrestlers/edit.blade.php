<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Edit Wrestler Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('wrestlers.update', $wrestler)" id="editWrestlerForm">
                    @method('PATCH')
                    @include('wrestlers.partials.form')
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="editWrestlerForm" />
                <x-form.buttons.submit form="editWrestlerForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
