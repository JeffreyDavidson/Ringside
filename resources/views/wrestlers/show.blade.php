<x-layouts.show-page :title="$wrestler->name">
    <x-slot:sidebar>
        <livewire:components.general-info :model="$wrestler" />
        <livewire:wrestlers.components.actions :wrestler="$wrestler" />
    </x-slot:sidebar>

    <livewire:wrestlers.tables.previous-title-championships :wrestlerId="$wrestler->id" defer.bundle />
    <livewire:wrestlers.tables.previous-matches :wrestlerId="$wrestler->id" defer.bundle />
    <livewire:wrestlers.tables.previous-tag-teams :wrestlerId="$wrestler->id" defer.bundle />
    <livewire:wrestlers.tables.previous-managers :wrestlerId="$wrestler->id" defer.bundle />
    <livewire:wrestlers.tables.previous-stables :wrestlerId="$wrestler->id" defer.bundle />
</x-layouts.show-page>
