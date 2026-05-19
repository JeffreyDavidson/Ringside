<x-layouts.show-page>
    <x-slot:sidebar>
        <x-tag-teams.show.general-info :$tagTeam />
    </x-slot:sidebar>

    <livewire:tag-teams.tables.previous-title-championships :tagTeamId="$tagTeam->id" />
    <livewire:tag-teams.tables.previous-matches :tagTeamId="$tagTeam->id" />
    <livewire:tag-teams.tables.previous-wrestlers :tagTeamId="$tagTeam->id" />
    <livewire:tag-teams.tables.previous-managers :tagTeamId="$tagTeam->id" />
    <livewire:tag-teams.tables.previous-stables :tagTeamId="$tagTeam->id" />
</x-layouts.show-page>
