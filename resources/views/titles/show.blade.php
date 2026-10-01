<x-layouts.show-page :title="$title->name">
    <x-slot:sidebar>
        <livewire:components.general-info :model="$title" />
        <livewire:titles.components.actions :title="$title" />
    </x-slot:sidebar>

    <livewire:titles.tables.title-history :titleId="$title->id" defer.bundle />
</x-layouts.show-page>
