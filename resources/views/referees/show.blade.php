<x-layouts.show-page :title="$referee->full_name">
    <x-slot:sidebar>
        <livewire:components.general-info :model="$referee" />
        <livewire:referees.components.actions :referee="$referee" />
    </x-slot:sidebar>

    <livewire:roster.availability.components.profile kind="referees" :personId="$referee->id" />

    <livewire:referees.tables.previous-matches :refereeId="$referee->id" defer.bundle />
</x-layouts.show-page>
