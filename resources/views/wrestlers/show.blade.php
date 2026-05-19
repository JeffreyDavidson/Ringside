<x-layouts.show-page>
    <x-slot:sidebar>
        <x-wrestlers.show.general-info :$wrestler />
    </x-slot:sidebar>

    <livewire:wrestlers.tables.previous-title-championships :wrestlerId="$wrestler->id" />
    <livewire:wrestlers.tables.previous-matches :wrestlerId="$wrestler->id" />
    <livewire:wrestlers.tables.previous-tag-teams :wrestlerId="$wrestler->id" />
    <livewire:wrestlers.tables.previous-managers :wrestlerId="$wrestler->id" />
    <livewire:wrestlers.tables.previous-stables :wrestlerId="$wrestler->id" />
</x-layouts.show-page>
