<x-layouts.show-page :title="$stable->name">
    <x-slot:sidebar>
        <x-stables.show.general-info :$stable />
    </x-slot:sidebar>

    <livewire:stables.tables.previous-wrestlers :stableId="$stable->id" defer.bundle />
    <livewire:stables.tables.previous-tag-teams :stableId="$stable->id" defer.bundle />
    <livewire:stables.tables.previous-managers :stableId="$stable->id" defer.bundle />
</x-layouts.show-page>
