<x-layouts.show-page :title="$venue->name">
    <x-slot:sidebar>
        <x-venues.show.general-info :$venue />
    </x-slot:sidebar>

    <livewire:venues.tables.previous-events :venueId="$venue->id" defer.bundle />
</x-layouts.show-page>
