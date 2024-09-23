<x-layouts.app>
    <x-container-fixed>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="grid gap-5 lg:gap-7.5">
                    <x-tag-teams.show.general-info :$tagTeam />
                </div>

                <div class="col-span-2">
                    <div class="flex flex-col gap-5 lg:gap-7.5">
                        {{-- <livewire:tag-teams.show.previous-title-championships-list :tagTeam="$tagTeam" />
                        <livewire:tag-teams.show.previous-matches-list :tagTeam="$tagTeam" />
                        <livewire:tag-teams.show.previous-wrestlers-list :tagTeam="$tagTeam" />
                        <livewire:tag-teams.show.previous-managers-list :tagTeam="$tagTeam" />
                        <livewire:tag-teams.show.previous-stables-list :tagTeam="$tagTeam" /> --}}
                    </div>
                </div>
            </div>
        </div>
    </x-container-fixed>
</x-layouts.app>
