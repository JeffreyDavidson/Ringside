<section class="min-w-0" aria-labelledby="availability-calendar-title">
    <header class="border-border mb-6 border-b pb-5">
        <h1 id="availability-calendar-title" class="font-display text-foreground text-3xl uppercase">
            {{ __('availability.title') }}
        </h1>
        <p class="text-muted-foreground mt-3 max-w-2xl">{{ __('availability.calendar_hint') }}</p>
        @if ($promotion)
            <p class="text-muted-foreground mt-2 text-sm">
                {{ __('availability.zone', ['zone' => $promotion->timezone]) }}
            </p>
        @endif
    </header>
    @if ($promotion === null)
        <p class="text-muted-foreground">{{ __('availability.choose_promotion') }}</p>
    @else
        <div class="border-border grid gap-4 border-b pb-5 md:grid-cols-3">
            <div>
                <label
                    for="availability-day"
                    class="text-foreground mb-2 block text-sm font-semibold"
                >{{ __('availability.day') }}</label>
                <x-form.input
                    id="availability-day"
                    type="date"
                    class="scheme-dark"
                    appearance="ringside"
                    wire:model.live="date"
                    aria-describedby="availability-day-error"
                />
                @error('date')
                    <p id="availability-day-error" class="text-error mt-2 flex gap-2 text-sm" role="alert">
                        <x-heroicon-s-exclamation-circle class="size-5 shrink-0" aria-hidden="true" />{{ $message }}
                    </p>
                @enderror
                <div class="mt-3 flex gap-2">
                    <button
                        type="button"
                        wire:click="moveDay(-1)"
                        class="text-foreground border-border-strong min-h-11 border px-3 py-2"
                    >
                        {{ __('availability.previous') }}
                    </button>
                    <button
                        type="button"
                        wire:click="moveDay(1)"
                        class="text-foreground border-border-strong min-h-11 border px-3 py-2"
                    >
                        {{ __('availability.next') }}
                    </button>
                </div>
            </div>
            <div>
                <label
                    for="availability-kind"
                    class="text-foreground mb-2 block text-sm font-semibold"
                >{{ __('availability.people') }}</label>
                <select
                    id="availability-kind"
                    wire:model.live="kind"
                    class="bg-surface-panel text-foreground border-border-strong min-h-11 w-full border px-3 py-2"
                >
                    <option value="">{{ __('availability.everyone') }}</option>
                    <option value="wrestlers">{{ __('availability.wrestlers') }}</option>
                    <option value="referees">{{ __('availability.referees') }}</option>
                </select>
            </div>
            <div>
                <label
                    for="availability-search"
                    class="text-foreground mb-2 block text-sm font-semibold"
                >{{ __('availability.search') }}</label>
                <x-form.input
                    id="availability-search"
                    type="search"
                    appearance="ringside"
                    wire:model.live.debounce.250ms="search"
                />
            </div>
        </div>
        @if ($eventIssues !== [])
            <section class="border-border border-b py-5" aria-labelledby="availability-affected-events">
                <h2 id="availability-affected-events" class="text-foreground font-semibold">
                    {{ __('availability.affected') }}
                </h2>
                <ul class="mt-3 space-y-3">
                    @foreach ($eventIssues as $issue)
                        <li class="text-warning" wire:key="availability-event-{{ $issue['event']->id }}">
                            <x-heroicon-o-exclamation-triangle class="mr-1 inline size-5" aria-hidden="true" /><a
                                href="{{ route('events.show', $issue['event']) }}"
                                class="inline-flex min-h-11 items-center underline underline-offset-4"
                                >{{ $issue['event']->name }}</a
                            ><span class="text-muted-foreground"> · {{ $issue['names'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        <div aria-live="polite" wire:loading class="text-muted-foreground py-3">{{ __('availability.loading') }}</div>
        <ul class="divide-border divide-y">
            @forelse ($entries ?? [] as $entry)
                <li
                    class="flex flex-col gap-2 py-5 sm:flex-row sm:items-start sm:justify-between"
                    wire:key="calendar-entry-{{ $entry->id }}"
                >
                    <div class="min-w-0">
                        <a
                            href="{{ route($entry->person_type === 'referee' ? 'referees.show' : 'wrestlers.show', $entry->person_id) }}"
                            class="text-foreground inline-flex min-h-11 items-center text-lg font-semibold underline underline-offset-4"
                        >{{ $entry->person_type === 'referee' ? $entry->person->full_name : $entry->person->name }}</a>
                        <p class="text-muted-foreground text-sm">
                            {{ $entry->person_type === 'referee' ? __('availability.referee') : __('availability.wrestler') }}
                        </p>
                        @if ($entry->reason)
                            <p class="text-muted-foreground mt-2 max-w-prose text-sm break-words">
                                {{ $entry->reason }}
                            </p>
                        @endif
                    </div>
                    <p class="text-foreground text-sm tabular-nums">{{ $entry->starts_on }} — {{ $entry->ends_on }}</p>
                </li>
            @empty
                @if (! $errors->has('date'))
                    <li class="text-muted-foreground py-8">{{ __('availability.no_day_entries') }}</li>
                @endif
            @endforelse
        </ul>
        @if ($entries) {{ $entries->links() }}@endif
    @endif
</section>
