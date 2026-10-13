<x-layouts.show-page :title="$event->name">
    <x-slot:sidebar>
        <x-events.show.general-info :$event />
    </x-slot:sidebar>

    <div class="mb-6 flex justify-end">
        <a
            href="{{ route('events.pack.show', $event) }}"
            class="border-ringside-line-bright border px-4 py-3 font-semibold"
        >{{ __('event-pack.title') }}</a>
    </div>

    @can('create', \App\Models\Matches\EventMatch::class)
        <div class="mb-6 flex justify-end">
            <x-buttons.primary
                data-test="add-event-match"
                onclick="Livewire.dispatch('openModal', { component: 'matches.modals.form-modal', arguments: { eventId: {{ $event->id }} } })"
            >
                {{ __('matches.add_event_match') }}
            </x-buttons.primary>
        </div>
    @endcan

    <livewire:matches.tables.matches-table :eventId="$event->id" defer.bundle />
</x-layouts.show-page>
