<x-layouts.show-page :title="$stable->name">
    <x-slot:sidebar>
        <livewire:components.general-info :model="$stable" />
        <livewire:stables.components.actions :stable="$stable" />
    </x-slot:sidebar>

    <livewire:stables.tables.previous-wrestlers :stableId="$stable->id" defer.bundle />
    <livewire:stables.tables.previous-tag-teams :stableId="$stable->id" defer.bundle />
    <livewire:stables.tables.previous-managers :stableId="$stable->id" defer.bundle />
</x-layouts.show-page>
