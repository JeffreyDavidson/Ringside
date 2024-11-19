<x-layouts.app>
    <x-container-fixed>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="grid gap-5 lg:gap-7.5">
                    <x-tag-teams.show.general-info :$tagTeam />
                </div>
            </div>
            <div class="col-span-2">
                <div class="flex flex-col gap-5 lg:gap-7.5">
                    {{-- <livewire:tag-teams.previous-title-championships-table :tagTeam="$tagTeam" /> --}}
                    {{-- <livewire:tag-teams.previous-matches-table :tagTeam="$tagTeam" /> --}}
                    <livewire:tag-teams.previous-wrestlers-table :tagTeamId="$tagTeam->id" />
                    <livewire:tag-teams.previous-managers-table :tagTeamId="$tagTeam->id" />
                    <livewire:tag-teams.previous-stables-table :tagTeamId="$tagTeam->id" />
                </div>
            </div>
        </div>
    </x-container-fixed>
</x-layouts.app>
