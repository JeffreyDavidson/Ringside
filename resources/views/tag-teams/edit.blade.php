<x-layouts.app>
    <x-slot:toolbar>
        <x-toolbar>
            <x-page-heading>Edit Tag Team</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('tag-teams.index')" label="Tag Teams" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('tag-teams.show', $tagTeam)" :label="$tagTeam->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Edit" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-card.header>
            <x-card.title class="m-0">
                <x-card.heading>Edit Tag Team Form</x-card.heading>
            </x-card.title>
        </x-card.header>
        <x-card.body>
            <x-form :action="route('tag-teams.update', $tagTeam)" id="editTagTeamForm">
                @method('PATCH')
                @include('tag-teams.partials.form')
            </x-form>
        </x-card.body>
        <x-card.footer>
            <x-form.buttons.reset form="editTagTeamForm"/>
            <x-form.buttons.submit form="editTagTeamForm"/>
        </x-card.footer>
    </x-card>
</x-layouts.app>
