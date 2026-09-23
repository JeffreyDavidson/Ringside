<x-layouts.show-page :title="$title->name">
    <x-slot:sidebar>
        <x-titles.show.general-info :$title />
    </x-slot:sidebar>

    <livewire:titles.tables.previous-title-championships :titleId="$title->id" />
</x-layouts.show-page>
