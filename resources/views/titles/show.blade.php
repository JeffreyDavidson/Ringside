<x-layouts.show-page :title="$title->name">
    <x-slot:sidebar>
        <x-titles.show.general-info :$title />
        <livewire:titles.components.actions :title="$title" />
    </x-slot:sidebar>

    <livewire:titles.tables.previous-title-championships :titleId="$title->id" defer.bundle />
</x-layouts.show-page>
