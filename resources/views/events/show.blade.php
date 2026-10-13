<x-layouts.show-page :title="$event->name">
    <x-slot:sidebar>
        <x-events.show.general-info :$event />
    </x-slot:sidebar>

    <div class="mb-6 flex flex-wrap justify-end gap-3">
        <a
            href="{{ route('events.pack.show', $event) }}"
            class="border-ringside-line-bright border px-4 py-3 font-semibold"
        >{{ __('event-pack.title') }}</a>
        <a
            href="{{ route('events.live-show.show', $event) }}"
            class="border-ringside-line-bright border px-4 py-3 font-semibold"
        >{{ __('live-show.title') }}</a>
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

    @if ($storylines->isNotEmpty())
        <section class="border-ringside-line mb-6 border-y py-4">
            <h2 class="mb-3 font-semibold">{{ __('storylines.related') }}</h2>
            @foreach ($storylines as $storyline)
                <a
                    href="{{ route('storylines.show', $storyline) }}"
                    class="block min-h-11 py-3 underline underline-offset-4"
                >{{ $storyline->title }}</a>
            @endforeach
        </section>
    @endif

    <livewire:matches.tables.matches-table :eventId="$event->id" defer.bundle />
</x-layouts.show-page>
