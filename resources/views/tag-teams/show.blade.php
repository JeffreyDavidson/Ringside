<x-layouts.show-page :title="$tagTeam->name">
    <x-slot:sidebar>
        <x-tag-teams.show.general-info :$tagTeam />
        <livewire:tag-teams.components.actions :tagTeam="$tagTeam" />
    </x-slot:sidebar>

    <livewire:tag-teams.tables.previous-title-championships :tagTeamId="$tagTeam->id" defer.bundle />
    <livewire:tag-teams.tables.previous-matches :tagTeamId="$tagTeam->id" defer.bundle />
    <livewire:tag-teams.tables.previous-wrestlers :tagTeamId="$tagTeam->id" defer.bundle />
    <livewire:tag-teams.tables.previous-managers :tagTeamId="$tagTeam->id" defer.bundle />
    <livewire:tag-teams.tables.previous-stables :tagTeamId="$tagTeam->id" defer.bundle />
</x-layouts.show-page>
