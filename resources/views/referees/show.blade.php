<x-layouts.show-page :title="$referee->full_name">
    <x-slot:sidebar>
        <x-referees.show.general-info :$referee />
    </x-slot:sidebar>

    <livewire:referees.tables.previous-matches :refereeId="$referee->id" />
</x-layouts.show-page>
