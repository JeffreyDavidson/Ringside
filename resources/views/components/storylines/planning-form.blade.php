@props(['mode', 'form', 'editingId', 'story', 'participants', 'events', 'selectedEvent', 'source', 'needsReload'])
@use('App\Enums\Storylines\StorylineFormMode')
@php
    $states = $mode === StorylineFormMode::StepStatus ? [\App\Enums\Storylines\StepStatus::Planned, \App\Enums\Storylines\StepStatus::Postponed, \App\Enums\Storylines\StepStatus::Cancelled] : \App\Enums\Storylines\StorylineStatus::cases();
    $statusOptions = collect($states)->mapWithKeys(fn ($state) => [$state->value => $state->label()])->all();

    if ($mode === StorylineFormMode::StoryStatus && $story->status === \App\Enums\Storylines\StorylineStatus::Archived) {
        $statusOptions = [$story->previous_status->value => __('storylines.restore').' · '.$story->previous_status->label()];
    }
@endphp
<form wire:submit="save" id="storyline-form" class="border-border bg-surface-panel scroll-mt-6 border p-5 sm:p-7">
    <h2 class="font-display mb-5 text-2xl">{{ $mode->label() }}</h2>
    @if ($errors->any())
        <div role="alert" class="border-error bg-error-surface text-error mb-5 border p-4">
            <x-heroicon-o-exclamation-triangle class="mr-2 inline size-5" />
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    @if ($needsReload)
        <p class="mb-3">{{ __('storylines.draft_preserved') }}</p>
        <x-button wire:click="reload" variant="secondary" class="mb-5">{{ __('storylines.reload') }}</x-button>
    @endif
    <fieldset
        @disabled($needsReload)
        wire:loading.attr="disabled"
        wire:target="save"
        wire:offline.attr="disabled"
        class="grid max-w-3xl gap-5"
    >
        @if (in_array($mode, [StorylineFormMode::Story, StorylineFormMode::Step, StorylineFormMode::Development], true))
            <x-form.inputs.text
                wire:model="form.heading"
                :label="__('storylines.'.($mode === StorylineFormMode::Story ? 'name' : 'heading'))"
                size="lg"
                class="min-h-11"
                required
                maxlength="160"
            />
            <x-form.inputs.textarea
                wire:model="form.description"
                :label="__('storylines.'.($mode === StorylineFormMode::Story ? 'premise' : 'description'))"
                size="lg"
                class="min-h-11"
                required
                maxlength="4000"
            />
            @if ($mode === StorylineFormMode::Story)
                <x-form.inputs.textarea
                    wire:model="form.payoff"
                    :label="__('storylines.payoff')"
                    size="lg"
                    class="min-h-11"
                    maxlength="4000"
                />
            @endif
        @endif
        @if (in_array($mode, [StorylineFormMode::Step, StorylineFormMode::Development], true))
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input
                    type="number"
                    wire:model="form.sequence"
                    :label="__('storylines.sequence')"
                    size="lg"
                    class="min-h-11"
                    min="1"
                    max="10000"
                    required
                />
                @if ($mode === StorylineFormMode::Development)
                    <x-form.input
                        type="date"
                        wire:model="form.occurredOn"
                        :label="__('storylines.occurred')"
                        size="lg"
                        class="min-h-11"
                        required
                    />
                @endif
            </div>
            <section class="border-border border-t pt-5">
                <h3 class="mb-3 font-semibold">{{ __('storylines.source') }}</h3>
                <p class="text-muted-foreground mb-4 text-sm">{{ __('storylines.source_hint') }}</p>
                <div class="grid gap-4">
                    <x-form.inputs.text
                        wire:model.live.debounce.300ms="eventSearch"
                        :label="__('storylines.event_search')"
                        size="lg"
                        class="min-h-11"
                    />
                    <x-form.inputs.select
                        wire:model.live="form.eventId"
                        :label="__('storylines.event')"
                        size="lg"
                        :options="$events->pluck('name', 'id')->all()"
                        :placeholder="__('storylines.no_event')"
                        class="min-h-11"
                    />
                    <p class="text-muted-foreground text-sm">{{ __('storylines.selection_hint') }}</p>
                    @if ($selectedEvent)
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-form.inputs.select
                                wire:model.live="form.matchId"
                                :label="__('storylines.match')"
                                size="lg"
                                :options="$selectedEvent->matches->mapWithKeys(fn ($match) => [$match->id => __('storylines.match_number', ['number' => $match->match_number])])->all()"
                                :placeholder="__('storylines.no_match')"
                                class="min-h-11"
                            />
                            <x-form.inputs.select
                                wire:model.live="form.segmentId"
                                :label="__('storylines.segment')"
                                size="lg"
                                :options="collect($selectedEvent->pack?->content['segments'] ?? [])->pluck('label', 'id')->all()"
                                :placeholder="__('storylines.no_segment')"
                                class="min-h-11"
                            />
                        </div>
                    @elseif ($mode === StorylineFormMode::Step)
                        <x-form.input
                            type="date"
                            wire:model.live="form.targetOn"
                            :label="__('storylines.target')"
                            size="lg"
                            class="min-h-11"
                        />
                    @endif
                    @if ($form->eventId)
                        <p class="text-muted-foreground">
                            {{ __('storylines.source_evidence') }}: {{ $source['context']['segment_label'] ?? $source['context']['match_label'] ?? $source['context']['event_name'] ?? '' }} · {{ $source['context']['live_state'] ? \App\Enums\Events\LiveItemStatus::tryFrom($source['context']['live_state'])?->label() ?? $source['context']['live_state'] : __('storylines.no_live') }}
                            @if ($form->matchId) ·{{ $source['context']['match_result'] ?? __('storylines.no_result') }}@endif
                        </p>
                    @endif
                    @foreach ($source['warnings'] as $warning)
                        <p class="text-warning">{{ $warning->label() }}</p>
                    @endforeach
                    <x-button
                        variant="secondary"
                        wire:click="reviewSource"
                        class="justify-self-start"
                    >{{ __('storylines.source_review') }}</x-button>
                </div>
            </section>
            @if ($mode === StorylineFormMode::Development)
                <fieldset class="border-border grid gap-3 border-t pt-5">
                    <legend class="font-semibold">{{ __('storylines.cast') }}</legend>
                    <p class="text-muted-foreground text-sm">{{ __('storylines.cast_hint') }}</p>
                    @foreach ($participants as $person)
                        <label class="flex min-h-11 items-center gap-3"
                            ><input
                                type="checkbox"
                                wire:model="form.participantIds"
                                value="{{ $person->id }}"
                                class="size-5"
                            />{{ $person->label }} · {{ $person->joined_on->toDateString() }}
                            @if ($person->left_on) —{{ $person->left_on->toDateString() }}@endif
                        </label>
                    @endforeach
                </fieldset>
            @endif
        @endif
        @if (in_array($mode, [StorylineFormMode::Participant, StorylineFormMode::ParticipantEnd], true))
            @if ($mode === StorylineFormMode::Participant)
                <x-form.inputs.select
                    wire:model.live="form.type"
                    :label="__('storylines.type')"
                    size="lg"
                    :options="['wrestler' => __('storylines.wrestler'), 'tag_team' => __('storylines.tag_team')]"
                    :placeholder="__('storylines.type')"
                    class="min-h-11"
                />
                <div
                    class="[&_input]:min-h-11 [&_button]:min-h-11 [&_li]:min-h-11"
                    wire:key="creative-kind-{{ $form->type }}"
                >
                    <x-form.inputs.roster-combobox
                        wire:model="form.participantId"
                        :label="__('storylines.participants')"
                        :emptyMessage="__('storylines.no_cast_matches')"
                        :kind="$form->type === 'tag_team' ? \App\Enums\Roster\BookableRosterKind::TagTeams : \App\Enums\Roster\BookableRosterKind::Wrestlers"
                    />
                </div>
                <x-form.inputs.text
                    wire:model="form.role"
                    :label="__('storylines.role')"
                    size="lg"
                    class="min-h-11"
                    maxlength="160"
                />
                <x-form.input
                    type="date"
                    wire:model="form.joinedOn"
                    :label="__('storylines.joined')"
                    size="lg"
                    class="min-h-11"
                    required
                />
            @else
                <x-form.input
                    type="date"
                    wire:model="form.leftOn"
                    :label="__('storylines.left')"
                    size="lg"
                    class="min-h-11"
                    required
                />
            @endif
        @endif
        @if (in_array($mode, [StorylineFormMode::StepStatus, StorylineFormMode::StoryStatus], true))
            <x-form.inputs.select
                wire:model="form.status"
                :label="__('storylines.status')"
                size="lg"
                required
                :options="$statusOptions"
                :placeholder="__('storylines.status')"
                class="min-h-11"
            />
        @endif
        @if ($mode !== StorylineFormMode::Participant || $editingId !== null)
            <x-form.inputs.textarea
                wire:model="form.reason"
                :label="__('storylines.reason')"
                :description="__('storylines.reason_hint')"
                size="lg"
                class="min-h-11"
                maxlength="2000"
            />
        @endif
        @if ($mode === StorylineFormMode::Retract)
            <label class="flex min-h-11 items-center gap-3"
                ><input
                    type="checkbox"
                    wire:model="form.reopenStep"
                    class="size-5"
                />{{ __('storylines.reopen') }}</label>
        @endif
        <div class="flex flex-wrap gap-3">
            <x-button type="submit">{{ __('storylines.save') }}</x-button
            ><x-button variant="secondary" wire:click="cancel">{{ __('storylines.cancel') }}</x-button
            ><span wire:loading role="status" class="text-muted-foreground">{{ __('storylines.loading') }}</span>
        </div>
    </fieldset>
</form>
