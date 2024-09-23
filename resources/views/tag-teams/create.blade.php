<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Create Tag Team Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('tag-teams.store')" id="createTagTeamForm">
                    <x-tag-teams.form :$tagTeam :$wrestlers />
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="createTagTeamForm" />
                <x-form.buttons.submit form="createTagTeamForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
