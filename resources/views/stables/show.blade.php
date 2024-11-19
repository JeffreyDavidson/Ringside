<x-layouts.app>
    <x-container-fixed>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="grid gap-5 lg:gap-7.5">
                    <x-stables.show.general-info :$stable />
                </div>
            </div>
            <div class="col-span-2">
                <div class="flex flex-col gap-5 lg:gap-7.5">
                    <livewire:stables.previous-wrestlers-table :stableId="$stable->id" />
                    <livewire:stables.previous-tag-teams-table :stableId="$stable->id" />
                    <livewire:stables.previous-managers-table :stableId="$stable->id" />
                </div>
            </div>
        </div>
    </x-container-fixed>
</x-layouts.app>
