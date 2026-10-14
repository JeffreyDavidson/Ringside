<div
    x-data="{ returnFocus: null }"
    @talent-editor-open.window="
        $nextTick(() => {
            const editor = document.getElementById('booking-editor');
            if (editor) {
                editor.scrollIntoView({ block: 'start', behavior: 'instant' });
                document.getElementById('booking-editor-heading').focus({ preventScroll: true });
            }
        })
    "
    @talent-editor-closed.window="$nextTick(() => document.getElementById(returnFocus)?.focus())"
    class="mx-auto flex max-w-5xl flex-col gap-4 pb-8 lg:gap-6"
    data-talent-bookings
>
    <nav>
        <a
            href="{{ route('events.show', $event) }}"
            class="inline-block min-h-11 py-3 underline underline-offset-4"
        >{{ __('talent-bookings.back') }}</a>
    </nav>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="font-display text-3xl">{{ __('talent-bookings.title') }}</h1>
            <p class="mt-2 text-lg font-semibold break-words">{{ $event->name }}</p>
            <p class="text-muted-foreground mt-1">
                {{ $event->local_date?->format('M j, Y · H:i') ?? __('talent-bookings.unscheduled') }} · {{ $context['timezone'] }}
            </p>
        </div>
        <x-button
            variant="secondary"
            wire:click="refreshState"
            wire:loading.attr="disabled"
        >{{ __('talent-bookings.refresh') }}</x-button>
    </header>
    <p class="text-muted-foreground max-w-prose">
        {{ __('talent-bookings.intro') }} {{ __('talent-bookings.private') }}
    </p>
    @if ($event->date?->isPast())
        <p class="text-warning">{{ __('talent-bookings.historical_warning') }}</p>
    @endif
    @if ($card_warning)
        <p class="text-warning flex items-start gap-2">
            <x-heroicon-s-exclamation-triangle class="mt-1 size-4 shrink-0" />{{ __('talent-bookings.card_warning') }}
        </p>
    @endif
    <dl
        class="border-border flex flex-wrap gap-x-8 gap-y-3 border-y py-4"
        aria-label="{{ __('talent-bookings.title') }}"
    >
        @foreach (['attention', 'confirmed', 'contacted', 'proposed', 'not_tracked', 'cancelled'] as $count)
            <div>
                <dt class="text-muted-foreground text-sm">
                    {{ $count === 'attention' ? __('talent-bookings.attention') : __('talent-bookings.states.'.$count) }}
                </dt>
                <dd class="text-xl font-semibold tabular-nums">{{ $counts[$count] }}</dd>
            </div>
        @endforeach
    </dl>
    <p class="text-muted-foreground text-sm">{{ __('talent-bookings.clear') }}: {{ $counts['confirmed_clear'] }}</p>
    @if ($message)
        <p role="status" class="text-success">{{ $message }}</p>
    @endif
    <div class="flex flex-col gap-2 sm:max-w-xs">
        <x-form.label for="booking-filter">{{ __('talent-bookings.filter') }}</x-form.label>
        <select
            id="booking-filter"
            wire:model.live="filter"
            class="border-outline bg-surface-panel min-h-11 border px-3"
        >
            <option value="all">{{ __('talent-bookings.all') }}</option>
            <option value="attention">{{ __('talent-bookings.attention') }}</option>
            @foreach (['not_tracked', 'proposed', 'contacted', 'confirmed', 'cancelled'] as $state)
                <option value="{{ $state }}">{{ __('talent-bookings.states.'.$state) }}</option>
            @endforeach
        </select>
    </div>
    <ul class="divide-border divide-y">
        @forelse ($rows as $row)
            <li
                wire:key="talent-{{ $row['key'] }}"
                class="flex flex-col gap-3 py-5 sm:flex-row sm:items-start sm:justify-between"
                data-person="{{ $row['key'] }}"
            >
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-semibold break-words">{{ $row['name'] }}</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        {{ $row['role'] }} · {{ __('talent-bookings.'.($row['on_card'] ? 'on_card' : 'off_card')) }}
                    </p>
                    @if ($row['matches'])
                        <p class="text-muted-foreground text-sm">
                            {{ __('talent-bookings.matches', ['numbers' => implode(', ', $row['matches'])]) }}
                        </p>
                    @endif
                    <p class="mt-2 font-semibold">
                        {{ __('talent-bookings.states.'.($row['booking']?->status->value ?? 'not_tracked')) }}
                    </p>
                    @foreach ($row['warnings'] as $warning)
                        <p class="text-warning mt-2 flex items-start gap-2">
                            <x-heroicon-s-exclamation-triangle
                                class="mt-1 size-4 shrink-0"
                            /><span>{{ __('talent-bookings.warnings.'.$warning) }}</span>
                        </p>
                    @endforeach
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <x-button
                        variant="secondary"
                        id="talent-edit-{{ $row['key'] }}"
                        @click.capture="returnFocus = $event.currentTarget.id"
                        wire:click="edit('{{ $row['type'] }}', {{ $row['id'] }})"
                        wire:loading.attr="disabled"
                    >{{ __('talent-bookings.edit') }}</x-button>
                    @if ($row['booking'])
                        <x-button
                            variant="secondary"
                            id="talent-history-{{ $row['key'] }}"
                            @click.capture="returnFocus = $event.currentTarget.id"
                            wire:click="showHistory('{{ $row['type'] }}', {{ $row['id'] }})"
                            wire:loading.attr="disabled"
                        >{{ __('talent-bookings.history') }}</x-button>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-muted-foreground py-6">
                {{ __('talent-bookings.'.(array_sum($counts) === 0 ? 'empty' : 'no_results')) }}
            </li>
        @endforelse
    </ul>
    @if ($personId)
        <section
            id="booking-editor"
            class="border-border scroll-mt-20 border-y py-5"
            aria-labelledby="booking-editor-heading"
        >
            <div class="flex items-start justify-between gap-4">
                <h2 id="booking-editor-heading" tabindex="-1" class="min-w-0 text-xl font-semibold break-words">
                    {{ $personName }}
                </h2>
                <x-button variant="secondary" wire:click="closeEditor">{{ __('talent-bookings.close') }}</x-button>
            </div>
            @if ($historyOpen)
                <ol class="divide-border mt-4 divide-y">
                    @forelse ($history ?? [] as $revision)
                        <li class="py-4">
                            <p class="font-semibold">
                                #{{ $revision->version }} · {{ $revision->actor_name }} · {{ $revision->created_at->copy()->setTimezone($context['timezone'])->format('M j, Y H:i') }}
                            </p>
                            <p>{{ __('talent-bookings.states.'.$revision->snapshot['status']) }}</p>
                            @if ($revision->snapshot['terms_need_review'] ?? false)
                                <p class="text-warning">{{ __('talent-bookings.warnings.reconfirmation') }}</p>
                            @endif
                            <p class="text-muted-foreground">
                                USD {{ $screen->fee($revision->snapshot['fee_minor'] ?? null) }}
                            </p>
                            <p class="mt-2 break-words whitespace-pre-wrap">
                                {{ $revision->snapshot['travel_notes'] ?? '' }}
                            </p>
                            <p class="mt-2 break-words whitespace-pre-wrap">{{ $revision->reason }}</p>
                            <details class="mt-3">
                                <summary class="min-h-11 cursor-pointer py-3 underline underline-offset-4">
                                    {{ __('talent-bookings.recorded_details') }}
                                </summary>
                                <p>
                                    {{ data_get($revision->snapshot, 'context.event_name') }} · {{ data_get($revision->snapshot, 'context.event_date') }} · {{ data_get($revision->snapshot, 'context.venue_name') ?? __('talent-bookings.venue_unset') }}
                                </p>
                                <p>
                                    {{ $revision->snapshot['arrival_at'] ?? __('talent-bookings.unknown') }} · {{ $revision->snapshot['arrival_timezone'] ?? '' }}
                                </p>
                            </details>
                        </li>
                    @empty
                        <li class="py-4">{{ __('talent-bookings.history_empty') }}</li>
                    @endforelse
                </ol>
                @if ($history) {{ $history->links() }} @endif
            @else
                <form wire:submit="save" class="mt-5 flex flex-col gap-5">
                    <x-form.error name="booking" />
                    @if ($booking?->status === \App\Enums\Events\TalentBookingStatus::Cancelled)
                        <p class="text-warning">{{ __('talent-bookings.reopen') }}</p>
                    @endif
                    <div class="flex flex-col gap-2">
                        <x-form.label for="talent-state">{{ __('talent-bookings.status') }}</x-form.label
                        ><select
                            id="talent-state"
                            wire:model.live="form.status"
                            class="border-outline bg-surface-panel min-h-11 border px-3"
                        >
                            @foreach (\App\Enums\Events\TalentBookingStatus::cases() as $state)
                                <option value="{{ $state->value }}">
                                    {{ __('talent-bookings.states.'.$state->value) }}
                                </option>
                            @endforeach</select
                        ><x-form.error name="form.status" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="flex flex-col gap-2">
                            <x-form.label for="talent-fee">{{ __('talent-bookings.fee') }}</x-form.label
                            ><x-form.input
                                class="min-h-11"
                                id="talent-fee"
                                wire:model="form.fee"
                                inputmode="decimal"
                                aria-describedby="talent-fee-hint"
                            />
                            <p id="talent-fee-hint" class="text-muted-foreground text-sm">
                                {{ __('talent-bookings.unknown_fee') }}
                            </p>
                            <x-form.error name="form.fee" />
                        </div>
                        <div class="flex min-w-0 flex-col gap-2">
                            <x-form.label for="talent-arrival">{{ __('talent-bookings.arrival') }}</x-form.label
                            ><x-form.input
                                class="min-h-11"
                                id="talent-arrival"
                                type="datetime-local"
                                wire:model="form.arrival"
                                aria-describedby="talent-arrival-hint"
                            />
                            <p id="talent-arrival-hint" class="text-muted-foreground text-sm">
                                {{ __('talent-bookings.arrival_hint', ['timezone' => $context['timezone']]) }}
                            </p>
                            <x-form.error name="form.arrival" />
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <x-form.label for="talent-travel">{{ __('talent-bookings.travel') }}</x-form.label
                        ><x-form.inputs.textarea
                            id="talent-travel"
                            wire:model="form.travelNotes"
                            maxlength="2000"
                            rows="3"
                        /><x-form.error name="form.travelNotes" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <x-form.label for="talent-reason">{{ __('talent-bookings.reason') }}</x-form.label
                        ><x-form.inputs.textarea id="talent-reason" wire:model="form.reason" maxlength="500" rows="2" />
                        <p class="text-muted-foreground text-sm">{{ __('talent-bookings.reason_hint') }}</p>
                        <x-form.error name="form.reason" />
                    </div>
                    @if ($booking?->confirmed_snapshot)
                        <details>
                            <summary class="min-h-11 cursor-pointer py-3 underline underline-offset-4">
                                {{ __('talent-bookings.agreed_details') }}
                            </summary>
                            <p>
                                {{ $booking->confirmed_snapshot['context']['event_date'] }} · {{ $booking->confirmed_snapshot['context']['timezone'] }} · {{ $booking->confirmed_snapshot['context']['venue_name'] ?? __('talent-bookings.venue_unset') }}
                            </p>
                            <p>
                                {{ __('talent-bookings.agreed_terms') }}: USD {{ $screen->fee($booking->confirmed_snapshot['terms']['fee_minor']) }}
                            </p>
                            <p class="break-words whitespace-pre-wrap">
                                {{ $booking->confirmed_snapshot['terms']['travel_notes'] }}
                            </p>
                            <p>
                                {{ $booking->confirmed_snapshot['terms']['arrival_at'] ?? __('talent-bookings.unknown') }}
                            </p>
                        </details>
                    @endif
                    @if ($form->status === 'confirmed')
                        <p class="text-muted-foreground max-w-prose">{{ __('talent-bookings.attest_help') }}</p>
                    @endif
                    <div class="flex flex-wrap gap-3">
                        @if ($form->status === 'confirmed')
                            <x-button
                                type="button"
                                wire:click="save(true)"
                                wire:loading.attr="disabled"
                            >{{ __('talent-bookings.attest') }}</x-button>
                            @if ($booking?->status === \App\Enums\Events\TalentBookingStatus::Confirmed)
                                <x-button
                                    type="submit"
                                    variant="secondary"
                                    wire:loading.attr="disabled"
                                >{{ __('talent-bookings.save_pending') }}</x-button>
                            @endif
                        @else
                            <x-button
                                type="submit"
                                wire:loading.attr="disabled"
                            >{{ __('talent-bookings.save') }}</x-button>
                        @endif
                        <span wire:loading wire:target="save" role="status">{{ __('talent-bookings.saving') }}</span>
                    </div>
                </form>
            @endif
        </section>
    @endif
    <section class="border-border border-t pt-5" aria-labelledby="add-person-heading">
        <h2 id="add-person-heading" class="text-xl font-semibold">{{ __('talent-bookings.add') }}</h2>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex flex-col gap-2">
                <x-form.label for="search-kind">{{ __('talent-bookings.kind') }}</x-form.label
                ><select
                    id="search-kind"
                    wire:model.live="searchKind"
                    class="border-outline bg-surface-panel min-h-11 border px-3"
                >
                    <option value="wrestlers">{{ __('talent-bookings.wrestlers') }}</option>
                    <option value="referees">{{ __('talent-bookings.referees') }}</option>
                </select>
            </div>
            <div class="[&_input]:min-h-11 min-w-0 flex-1" wire:key="talent-search-{{ $searchKind }}">
                <x-form.inputs.roster-combobox
                    :label="__('talent-bookings.choose')"
                    :kind="\App\Enums\Roster\BookableRosterKind::tryFrom($searchKind) ?? \App\Enums\Roster\BookableRosterKind::Wrestlers"
                    wire:model="selectedPerson"
                />
            </div>
            <x-button
                variant="secondary"
                id="talent-open-person"
                @click.capture="returnFocus = $event.currentTarget.id"
                wire:click="addPerson"
                wire:loading.attr="disabled"
            >{{ __('talent-bookings.open') }}</x-button>
        </div>
    </section>
</div>
