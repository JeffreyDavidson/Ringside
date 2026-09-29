<x-layouts.show-page :title="$manager->full_name">
    <x-slot:sidebar>
        <x-managers.show.general-info :$manager />
        <livewire:managers.components.actions :manager="$manager" />
    </x-slot:sidebar>

    <livewire:managers.tables.previous-wrestlers :managerId="$manager->id" defer.bundle />
    <livewire:managers.tables.previous-tag-teams :managerId="$manager->id" defer.bundle />
    <livewire:managers.tables.previous-stables :managerId="$manager->id" defer.bundle />
</x-layouts.show-page>
