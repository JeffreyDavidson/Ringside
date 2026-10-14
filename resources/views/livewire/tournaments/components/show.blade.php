<div
    x-data="{}"
    x-on:tournament-panel-opened.window="$nextTick(() => $refs.panel?.focus())"
    class="mx-auto flex w-full max-w-6xl min-w-0 flex-col gap-6 pb-8"
>
    <a
        href="{{ route('tournaments.index') }}"
        class="min-h-11 self-start py-3 underline underline-offset-4"
    >{{ __('tournaments.back') }}</a>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="font-display text-3xl break-words">{{ $tournament->name }}</h1>
            <p class="text-muted-foreground mt-2 text-sm">
                {{ __('tournaments.kind.'.$tournament->entrant_kind->value) }} · {{ __('tournaments.slots',['count'=>$tournament->capacity]) }}
            </p>
            <p class="mt-2 font-semibold">{{ __('tournaments.status.'.$tournament->status->value) }}</p>
            @if ($tournament->description)
                <p class="text-muted-foreground mt-3 max-w-2xl whitespace-pre-line">{{ $tournament->description }}</p>
            @endif
        </div>
        <x-button variant="secondary" wire:click="refreshState">{{ __('tournaments.refresh') }}</x-button>
    </header>
    @if ($nextBout && in_array($tournament->status, [\App\Enums\Tournaments\TournamentStatus::Draft, \App\Enums\Tournaments\TournamentStatus::Active], true))
        <p class="border-primary border-l-2 pl-3">
            {{ __('tournaments.next') }} {{ __('tournaments.bout_label', ['round' => $nextBout->node->round, 'number' => $nextBout->node->position]) }} · {{ $nextBout->link ? ($nextBout->link->match->match_finish ? __('tournaments.rematch') : __('tournaments.awaiting_result')) : __('tournaments.ready') }}
        </p>
    @endif
    @if ($notice)
        <p role="status" class="text-success">{{ $notice }}</p>
    @endif
    @error('operation')
        <x-tournaments.error>{{ $message }}</x-tournaments.error>
    @enderror
    @if ($tournament->status === \App\Enums\Tournaments\TournamentStatus::Completed && $bouts->last()?->winner)
        <div class="border-border border-y py-5">
            <p class="text-muted-foreground text-sm">{{ __('tournaments.winner') }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ $bouts->last()->winner->competitor->name }}</p>
            @if ($bouts->last()->node->walkover_entry_id)
                <p>{{ __('tournaments.walkover') }}</p>
            @endif
        </div>
    @endif
    @can('update', $tournament)
        @if ($tournament->status === \App\Enums\Tournaments\TournamentStatus::Draft)
            <details open class="border-border border-y py-4">
                <summary class="min-h-11 cursor-pointer font-semibold">{{ __('tournaments.setup') }}</summary>
                <form wire:submit="save" class="mt-4 flex flex-col gap-4">
                    <x-tournaments.setup :form="$form" :labels="$labels" :kind="$kind" /><x-button
                        type="submit"
                        wire:loading.attr="disabled"
                        >{{ __('tournaments.save_draft') }}</x-button>
                </form>
            </details>
        @else
            <details class="border-border border-y py-3">
                <summary class="min-h-11 cursor-pointer font-semibold">{{ __('tournaments.edit_name') }}</summary>
                <form wire:submit="rename" class="flex flex-col gap-4">
                    <x-form.inputs.text wire:model="form.name" :label="__('tournaments.name')" /><x-form.inputs.textarea
                        wire:model="form.description"
                        :label="__('tournaments.description')"
                    /><x-button type="submit" wire:loading.attr="disabled">{{ __('tournaments.save') }}</x-button>
                </form>
            </details>
        @endif
    @endcan
    @if ($draftOperation !== '')
        <section
            x-ref="panel"
            tabindex="-1"
            class="border-outline focus-visible:outline-foreground border p-4 focus-visible:outline-2 sm:p-6"
            aria-labelledby="tournament-operation-title"
        >
            <form wire:submit="confirm" class="flex flex-col gap-4">
                <h2 id="tournament-operation-title" class="text-xl font-semibold">
                    {{ __('tournaments.operations.'.$draftOperation) }}
                </h2>
                @if (in_array($draftOperation, ['book', 'plan'], true))
                    <x-form.inputs.select
                        wire:model="eventId"
                        :label="__('tournaments.event')"
                        :options="$events->mapWithKeys(fn ($event) => [$event->id => $event->name.' · '.(($event->date === null ? null : \App\Models\Promotions\Promotion::toLocalTime($tournament->promotion, $event->date)->format('Y-m-d H:i T')) ?? __('tournaments.unscheduled'))])->all()"
                        :placeholder="__('tournaments.choose_event')"
                        class="min-h-11"
                    />
                @endif
                @if ($draftOperation === 'book')
                    <p class="text-muted-foreground">
                        {{ $selected?->first?->competitor->name }} {{ __('tournaments.versus') }} {{ $selected?->second?->competitor->name }}
                    </p>
                    <x-form.inputs.roster-combobox
                        wire:model="refereeIds"
                        :label="__('tournaments.referees')"
                        :kind="\App\Enums\Roster\BookableRosterKind::Referees"
                        :labels="$refereeLabels"
                        :multiple="true"
                    />
                    <x-form.inputs.select
                        wire:model="stipulationId"
                        :label="__('tournaments.stipulation')"
                        :options="$stipulations->pluck('name', 'id')->all()"
                        :placeholder="__('tournaments.none')"
                        class="min-h-11"
                    />
                    @if ($selected?->node->round === (int) log($tournament->capacity, 2))
                        <x-form.inputs.select
                            wire:model="titleId"
                            :label="__('tournaments.final_title')"
                            :options="$titles->pluck('name', 'id')->all()"
                            :placeholder="__('tournaments.none')"
                            class="min-h-11"
                        />
                    @endif
                    <p class="text-muted-foreground text-sm">{{ __('tournaments.booking_hint') }}</p>
                @endif
                @if ($entryId !== null)
                    <p class="font-semibold">{{ $entries->sole('id', $entryId)->competitor->name }}</p>
                @endif
                @if ($draftOperation === 'walkover')
                    <p>
                        {{ $selected?->first?->competitor->name }} {{ __('tournaments.versus') }} {{ $selected?->second?->competitor->name }}
                    </p>
                @endif
                @if ($draftOperation === 'replace')
                    <x-form.inputs.roster-combobox
                        wire:model="replacementId"
                        :label="__('tournaments.replacement')"
                        :kind="$kind"
                        :labels="$replacementLabels"
                    />
                @endif
                @if (in_array($draftOperation, ['reset', 'unbook', 'withdraw', 'replace', 'walkover', 'cancel'], true))
                    <p>{{ __('tournaments.review_hint') }}</p>
                    <ul class="text-muted-foreground list-inside list-disc text-sm">
                        @forelse ($affected as $affectedBout)
                            @if ($affectedBout->link)
                                <li>
                                    {{ __('tournaments.bout_label',['round'=>$affectedBout->node->round,'number'=>$affectedBout->node->position]) }} · {{ $affectedBout->link->match->event->name }} · {{ $affectedBout->link->match->match_finish ? __('tournaments.retained_result') : __('tournaments.remove_unplayed') }}
                                </li>
                            @endif
                        @empty
                            <li>{{ __('tournaments.no_bookings_affected') }}</li>
                        @endforelse
                    </ul>
                    <x-form.inputs.textarea wire:model="reason" :label="__('tournaments.reason')" />
                    @error('reason')
                        <x-tournaments.error>{{ $message }}</x-tournaments.error>
                    @enderror
                    <label class="flex min-h-11 items-center gap-3"
                        ><input
                            type="checkbox"
                            wire:model="confirmed"
                            class="size-5"
                        /><span>{{ __('tournaments.confirm_review') }}</span></label>
                    @error('confirmed')
                        <x-tournaments.error>{{ $message }}</x-tournaments.error>
                    @enderror
                @endif
                <div class="flex flex-wrap gap-3">
                    <x-button type="submit" wire:loading.attr="disabled">{{ __('tournaments.confirm') }}</x-button
                    ><x-button variant="secondary" wire:click="cancelDraft">{{ __('tournaments.close') }}</x-button>
                </div>
            </form>
        </section>
    @endif
    <nav aria-label="{{ __('tournaments.views') }}" class="flex gap-3">
        <x-button
            wire:click="view('rounds')"
            :variant="$view === 'rounds' ? 'primary' : 'secondary'"
            :aria-pressed="$view === 'rounds' ? 'true' : 'false'"
            >{{ __('tournaments.rounds') }}</x-button
        ><x-button
            wire:click="view('bracket')"
            :variant="$view === 'bracket' ? 'primary' : 'secondary'"
            :aria-pressed="$view === 'bracket' ? 'true' : 'false'"
            >{{ __('tournaments.bracket') }}</x-button>
    </nav>
    @if ($view === 'bracket')
        <p class="text-muted-foreground text-sm">{{ __('tournaments.bracket_hint') }}</p>
        <div
            class="max-w-full overflow-x-auto overscroll-x-contain pb-4"
            tabindex="0"
            role="region"
            aria-label="{{ __('tournaments.bracket') }}"
        >
            <div
                x-data="tournamentBracket"
                class="relative grid min-w-max gap-x-6 gap-y-4"
                style="grid-template-columns:repeat({{ (int)log($tournament->capacity,2) }},minmax(15rem,1fr))"
            >
                <svg wire:ignore data-bracket-lines aria-hidden="true" class="text-muted-foreground pointer-events-none absolute inset-0 size-full fill-none"></svg>
                @for ($round = 1; $round <= (int) log($tournament->capacity, 2); $round++)
                    <h2 class="font-semibold" style="grid-column:{{ $round }};grid-row:1">
                        {{ __('tournaments.round',['round'=>$round]) }}
                    </h2>
                @endfor
                @foreach ($bouts as $bout)
                    <div
                        wire:key="bracket-node-{{ $bout->node->id }}"
                        class="relative flex items-center"
                        style="grid-column:{{ $bout->node->round }};grid-row:{{ ($bout->node->position-1)*(2**($bout->node->round-1))+2 }} / span {{ 2**($bout->node->round-1) }}"
                    >
                        <x-tournaments.bout :bout="$bout" :tournament="$tournament" :available-ids="$availableIds" />
                    </div>
                @endforeach
            </div>
        </div>
    @else
        @foreach ($bouts->groupBy(fn ($bout) => $bout->node->round) as $round => $roundBouts)
            <section wire:key="round-{{ $round }}" aria-labelledby="round-{{ $round }}">
                <h2 id="round-{{ $round }}" class="mb-3 text-xl font-semibold">
                    {{ __('tournaments.round',['round'=>$round]) }}
                </h2>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($roundBouts as $bout)
                        @if (! ($bout->complete && $bout->winner === null))
                            <x-tournaments.bout
                                :bout="$bout"
                                :tournament="$tournament"
                                :available-ids="$availableIds"
                            />
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
    <details class="border-border border-y py-4">
        <summary class="min-h-11 cursor-pointer text-lg font-semibold">{{ __('tournaments.entrants') }}</summary>
        <ol class="divide-border divide-y">
            @foreach ($entries as $entry)
                <li wire:key="entry-{{ $entry->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div>
                        {{ $entry->seed }}. {{ $entry->competitor->name }}
                        @if ($entry->withdrawn)
                            <span class="text-warning">{{ __('tournaments.withdrawn') }}</span>
                        @endif
                    </div>
                    @can('update', $tournament)
                        @if ($tournament->status === \App\Enums\Tournaments\TournamentStatus::Active && ! $entry->withdrawn)
                            <div class="flex gap-2">
                                <x-button
                                    variant="secondary"
                                    wire:click="begin('replace',{{ $entry->id }})"
                                    >{{ __('tournaments.replace') }}</x-button
                                ><x-button
                                    variant="destructive"
                                    wire:click="begin('withdraw',{{ $entry->id }})"
                                    >{{ __('tournaments.withdraw') }}</x-button>
                            </div>
                        @endif
                    @endcan
                </li>
            @endforeach
        </ol>
    </details>
    @can('update', $tournament)
        @if (in_array($tournament->status, [\App\Enums\Tournaments\TournamentStatus::Draft, \App\Enums\Tournaments\TournamentStatus::Active], true))
            <x-button
                variant="destructive"
                wire:click="begin('cancel')"
                class="self-start"
            >{{ __('tournaments.cancel_tournament') }}</x-button>
        @endif
    @endcan
</div>
