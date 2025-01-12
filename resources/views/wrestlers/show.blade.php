tables.<x-layouts.app>
    <x-container-fluid>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="grid gap-5 lg:gap-7.5">
                    <x-wrestlers.show.general-info :$wrestler />
                </div>
            </div>
            <div class="col-span-2">
                <div class="flex flex-col gap-5 lg:gap-7.5">
                    {{-- <livewire:wrestlers.tables.previous-title-championships-table :$wrestler /> --}}
                    {{-- <livewire:wrestlers.tables.previous-matches-table :$wrestler /> --}}
                    <livewire:wrestlers.tables.previous-tag-teams-table :wrestlerId="$wrestler->id" />
                    <livewire:wrestlers.tables.previous-managers-table :wrestlerId="$wrestler->id" />
                    <livewire:wrestlers.tables.previous-stables-table :wrestlerId="$wrestler->id" />
                </div>
            </div>
        </div>
    </x-container-fluid>
</x-layouts.app>
