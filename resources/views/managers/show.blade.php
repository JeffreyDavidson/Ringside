<x-layouts.show-page>
    <x-slot:sidebar>
        <x-managers.show.general-info :$manager />
    </x-slot:sidebar>

    <livewire:managers.tables.previous-wrestlers :managerId="$manager->id" />
    <livewire:managers.tables.previous-tag-teams :managerId="$manager->id" />
    <livewire:managers.tables.previous-stables :managerId="$manager->id" />
</x-layouts.show-page>
