<x-layouts.app>
    <x-container-fluid>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Edit Tag Team Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('tag-teams.update', $tagTeam)" id="editTagTeamForm">
                    @method('PATCH')
                    <x-tag-teams.form :$tagTeam :$wrestlers />
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="editTagTeamForm" />
                <x-form.buttons.submit form="editTagTeamForm" />
            </x-card.footer>
        </x-card>
    </x-container-fluid>
</x-layouts.app>
