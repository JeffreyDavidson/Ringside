<x-layouts.app>
    <x-container-fluid>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="grid gap-5 lg:gap-7.5">
                    <x-tag-teams.show.general-info :$tagTeam />
                </div>
            </div>
            <div class="col-span-2">
                <div class="flex flex-col gap-5 lg:gap-7.5">
                    <livewire:tag-teams.tables.previous-title-championships :tagTeamId="$tagTeam->id" />
                    <livewire:tag-teams.tables.previous-matches :tagTeamId="$tagTeam->id" />
                    <livewire:tag-teams.tables.previous-wrestlers :tagTeamId="$tagTeam->id" />
                    <livewire:tag-teams.tables.previous-managers :tagTeamId="$tagTeam->id" />
                    <livewire:tag-teams.tables.previous-stables :tagTeamId="$tagTeam->id" />
                </div>
            </div>
        </div>
    </x-container-fluid>
</x-layouts.app>
