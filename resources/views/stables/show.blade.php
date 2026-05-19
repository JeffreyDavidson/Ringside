<x-layouts.show-page>
    <x-slot:sidebar>
        <x-stables.show.general-info :$stable />
    </x-slot:sidebar>

    <livewire:stables.tables.previous-wrestlers :stableId="$stable->id" />
    <livewire:stables.tables.previous-tag-teams :stableId="$stable->id" />
    <livewire:stables.tables.previous-managers :stableId="$stable->id" />
</x-layouts.show-page>
