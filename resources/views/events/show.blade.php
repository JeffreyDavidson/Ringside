<x-layouts.show-page>
    <x-slot:sidebar>
        <x-events.show.general-info :$event />
    </x-slot:sidebar>

    <livewire:matches.tables.matches-table :eventId="$event->id" />
</x-layouts.show-page>
