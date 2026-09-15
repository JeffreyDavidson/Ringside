<x-layouts.show-page>
    <x-slot:sidebar>
        <x-events.show.general-info :$event />
    </x-slot:sidebar>

    @can('create', \App\Models\Matches\EventMatch::class)
        <div class="mb-6 flex justify-end">
            <x-buttons.primary
                data-test="add-event-match"
                @click="$dispatch('openModal', { component: 'matches.modals.form-modal', arguments: { eventId: {{ $event->id }} } })"
            >
                Add Event Match
            </x-buttons.primary>
        </div>
    @endcan

    <livewire:matches.tables.matches-table :eventId="$event->id" />
</x-layouts.show-page>
