<x-layouts.show-page :title="$event->name">
    <x-slot:sidebar>
        <x-events.show.general-info :$event />
    </x-slot:sidebar>

    <div class="mb-6 flex flex-wrap justify-end gap-3">
        @can('viewBudget', $event)
            <a
                href="{{ route('events.budget.show', $event) }}"
                class="border-border-strong border px-4 py-3 font-semibold"
            >{{ __('event-budget.title') }}</a>
        @endcan
        @can('preparePublication', $event)
            <a
                href="{{ route('events.public-pages.edit', $event) }}"
                class="border-border-strong border px-4 py-3 font-semibold"
            >{{ __('public-events.editor_title') }}</a>
        @endcan
        @can('update', $event)
            <a
                href="{{ route('events.talent-bookings.show', $event) }}"
                class="border-border-strong border px-4 py-3 font-semibold"
            >{{ __('talent-bookings.title') }}</a>
        @endcan
        <a
            href="{{ route('events.pack.show', $event) }}"
            class="border-border-strong border px-4 py-3 font-semibold"
        >{{ __('event-pack.title') }}</a>
        <a
            href="{{ route('events.live-show.show', $event) }}"
            class="border-border-strong border px-4 py-3 font-semibold"
        >{{ __('live-show.title') }}</a>
        @can('manageSponsors', $event)
            <x-button
                tag="a"
                variant="secondary"
                :href="route('events.sponsors.show', $event)"
            >{{ __('sponsors.title') }}</x-button>
        @endcan
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
        <section class="border-border mb-6 border-y py-4">
            <h2 class="mb-3 font-semibold">{{ __('storylines.related') }}</h2>
            @foreach ($storylines as $storyline)
                <a
                    href="{{ route('storylines.show', $storyline) }}"
                    class="block min-h-11 py-3 underline underline-offset-4"
                >{{ $storyline->title }}</a>
            @endforeach
        </section>
    @endif

    @php
        $calendarUnavailable = app(\App\Queries\Events\EventRosterAvailabilityQuery::class)->unavailable($event);
    @endphp
    @if ($event->date === null)
        <p class="text-muted-foreground mb-5">{{ __('availability.not_checked') }}</p>
    @elseif ($calendarUnavailable->isNotEmpty() && ($event->date >= now() || $event->matches()->whereNull('match_finish')->exists()))
        <div class="border-border text-warning mb-5 flex gap-3 border p-4" role="status">
            <x-heroicon-o-exclamation-triangle class="size-5 shrink-0" aria-hidden="true" />
            <div>
                <p class="font-semibold">{{ __('availability.card_warning') }}</p>
                <p class="text-muted-foreground mt-2">
                    {{ $calendarUnavailable->map(fn ($person) => $person instanceof \App\Models\Roster\Referees\Referee ? $person->full_name : $person->name)->join(', ') }} · {{ $event->local_date?->toDateString() }}
                </p>
            </div>
        </div>
    @endif

    <livewire:matches.tables.matches-table :eventId="$event->id" defer.bundle />
</x-layouts.show-page>
