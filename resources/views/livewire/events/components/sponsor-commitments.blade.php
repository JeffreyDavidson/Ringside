<div x-data="sponsorTracking" class="mx-auto flex max-w-6xl flex-col gap-6 pb-8">
    <nav class="flex flex-wrap gap-x-6 gap-y-3 text-sm">
        <a
            class="min-h-11 py-3 underline underline-offset-4"
            href="{{ route('sponsors.index') }}"
        >{{ __('sponsors.directory') }}</a>
        @if (! $event->trashed())
            <a
                class="min-h-11 py-3 underline underline-offset-4"
                href="{{ route('events.show', $event) }}"
            >{{ __('event-pack.back') }}</a>
            <a
                class="min-h-11 py-3 underline underline-offset-4"
                href="{{ route('events.pack.show', $event) }}"
            >{{ __('event-pack.title') }}</a>
        @endif
    </nav>
    <header class="border-border flex flex-col gap-3 border-b pb-6 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="font-display text-2xl leading-tight break-words lg:text-3xl">
                {{ __('sponsors.title') }} · {{ $event->name }}
            </h1>
            <p class="text-muted-foreground mt-2 max-w-prose text-sm">{{ __('sponsors.private_help') }}</p>
        </div>
        @if (! $event->trashed() && $event->promotion_id !== null)
            <x-button
                wire:click="newCommitment"
                wire:loading.attr="disabled"
                x-bind:disabled="! online || requestFailed"
            >{{ __('sponsors.new_promise') }}</x-button>
        @endif
    </header>
    <div x-cloak x-show="! online || requestFailed" role="alert" class="border-warning border p-4">
        <p>{{ __('sponsors.online_help') }}</p>
        <a
            href="{{ route('events.sponsors.show', $event) }}"
            class="inline-block min-h-11 py-3 underline"
        >{{ __('sponsors.reload') }}</a>
    </div>
    @if ($event->trashed())
        <p class="text-warning">{{ __('sponsors.event_archived') }}</p>
    @endif
    @if ($notice)
        <p role="status" class="border-success border p-4">{{ $notice }}</p>
    @endif
    @if ($errors->any())
        <div role="alert" class="border-error bg-error-surface text-error border p-4">
            <p class="flex items-center gap-2 font-semibold">
                <x-heroicon-o-exclamation-circle
                    class="size-5 shrink-0"
                    aria-hidden="true"
                />{{ __('event-pack.save_failed') }}
            </p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($formShown)
        <form
            wire:submit="save"
            x-data="{ dirty: false }"
            @input="dirty = true"
            @beforeunload.window="
                if (dirty) {
                    $event.preventDefault();
                    $event.returnValue = '';
                }
            "
            class="border-border bg-surface-panel border-y p-4 sm:p-6"
            data-sponsor-draft
        >
            <fieldset x-bind:disabled="! online || requestFailed" class="flex min-w-0 flex-col gap-5">
                <legend class="mb-4 text-xl font-semibold">
                    {{ __($draftId === null ? 'sponsors.new_promise' : 'sponsors.edit_promise') }}
                </legend>
                <p x-cloak x-show="dirty" class="text-warning">{{ __('event-pack.unsaved') }}</p>
                <div class="grid min-w-0 gap-5 md:grid-cols-2">
                    <div class="min-w-0">
                        <x-form.inputs.text
                            class="min-h-11"
                            wire:model.live.debounce.300ms="sponsorSearch"
                            :label="__('sponsors.search')"
                        />
                        <p class="my-2 text-sm">
                            {{ __('sponsors.selected') }}:
                            <strong>{{ $selected?->name ?? __('sponsors.not_set') }}</strong>
                        </p>
                        <div class="border-border max-h-44 overflow-auto border">
                            @forelse ($sponsors as $sponsor)
                                <button
                                    type="button"
                                    wire:key="choice-{{ $sponsor->id }}"
                                    data-sponsor-choice="{{ $sponsor->id }}"
                                    wire:click="chooseSponsor({{ $sponsor->id }})"
                                    class="hover:bg-surface-hover min-h-11 w-full px-3 py-2 text-left break-words focus-visible:outline-2"
                                >
                                    {{ $sponsor->name }}<span
                                        class="text-muted-foreground block text-sm"
                                        >{{ $sponsor->contact_name ?? __('sponsors.record_number', ['number' => $sponsor->id]) }}</span>
                                </button>
                            @empty
                                <p class="text-muted-foreground p-3 text-sm">{{ __('sponsors.empty_sponsors') }}</p>
                            @endforelse
                        </div>
                    </div>
                    <x-form.inputs.select
                        class="min-h-11"
                        wire:model="form.type"
                        :label="__('sponsors.type')"
                        :options="collect(\App\Enums\Events\SponsorCommitmentType::cases())->mapWithKeys(fn (\App\Enums\Events\SponsorCommitmentType $type): array => [$type->value => $type->label()])->all()"
                        :selected="$form->type"
                    />
                </div>
                <x-form.inputs.textarea wire:model="form.description" :label="__('sponsors.description')" rows="2" />
                <div class="grid min-w-0 gap-5 md:grid-cols-2">
                    <x-form.inputs.text class="min-h-11" wire:model="form.owner" :label="__('sponsors.owner')" />
                    <x-form.input
                        class="min-h-11"
                        type="datetime-local"
                        wire:model="form.deadline"
                        :label="__('sponsors.deadline')"
                    />
                    <x-form.inputs.text class="min-h-11" wire:model="form.timezone" :label="__('sponsors.timezone')" />
                    <x-form.inputs.text
                        class="min-h-11"
                        wire:model="form.deadlineOffset"
                        :label="__('sponsors.offset')"
                    />
                </div>
                <p class="text-muted-foreground max-w-prose text-sm">{{ __('sponsors.offset_help') }}</p>
                <x-form.inputs.select
                    class="min-h-11"
                    wire:model.live="form.status"
                    :label="__('sponsors.state')"
                    :options="collect(\App\Enums\Events\SponsorCommitmentStatus::cases())->mapWithKeys(fn (\App\Enums\Events\SponsorCommitmentStatus $status): array => [$status->value => $status->label()])->all()"
                    :selected="$form->status"
                />
                @if ($form->status === 'completed')
                    <x-form.inputs.textarea wire:model="form.evidence" :label="__('sponsors.evidence')" rows="2" />
                    <x-form.inputs.text
                        class="min-h-11"
                        wire:model="form.evidenceUrl"
                        :label="__('sponsors.evidence_url')"
                    />
                    <div class="grid min-w-0 gap-5 md:grid-cols-2">
                        <x-form.input
                            class="min-h-11"
                            type="datetime-local"
                            wire:model="form.completedAt"
                            :label="__('sponsors.completed_at')"
                            :description="__('sponsors.completion_time_help')"
                        />
                        <x-form.inputs.text
                            class="min-h-11"
                            wire:model="form.completedOffset"
                            :label="__('sponsors.offset')"
                        />
                    </div>
                @endif
                <p class="text-muted-foreground max-w-prose text-sm">
                    {{ __('sponsors.complete_help') }} {{ __('sponsors.terminal_help') }}
                </p>
                <x-form.inputs.textarea wire:model="form.reason" :label="__('sponsors.reason')" rows="2" />
                <label class="flex min-h-11 items-center gap-3"
                    ><input
                        type="checkbox"
                        wire:model="form.reviewed"
                        class="size-5"
                    />{{ __('sponsors.review_event') }}</label>
                <details wire:ignore.self class="border-border border-t pt-3">
                    <summary class="min-h-11 cursor-pointer py-3 font-semibold">
                        {{ __('sponsors.private_notes') }} · {{ __('sponsors.agreement') }}
                    </summary>
                    <div class="mt-3 flex flex-col gap-5">
                        <x-form.inputs.text
                            class="min-h-11"
                            wire:model="form.agreement"
                            :label="__('sponsors.agreement')"
                        />
                        <p class="text-muted-foreground max-w-prose text-sm">{{ __('sponsors.agreement_help') }}</p>
                        <x-form.inputs.textarea
                            wire:model="form.notes"
                            :label="__('sponsors.private_notes')"
                            rows="3"
                        />
                    </div>
                </details>
                <details wire:ignore.self class="border-border border-t pt-3">
                    <summary data-sponsor-wording class="min-h-11 cursor-pointer py-3 font-semibold">
                        {{ __('sponsors.pack_wording') }}
                    </summary>
                    <div class="mt-3 flex flex-col gap-5">
                        <p class="text-muted-foreground max-w-prose text-sm">{{ __('sponsors.pack_wording_help') }}</p>
                        <x-form.inputs.text
                            class="min-h-11"
                            wire:model="form.instructionLabel"
                            :label="__('sponsors.instruction_label')"
                        />
                        <x-form.inputs.textarea
                            wire:model="form.instruction"
                            :label="__('sponsors.instruction')"
                            rows="3"
                        />
                        <x-form.inputs.text
                            class="min-h-11"
                            wire:model="form.instructionOwner"
                            :label="__('sponsors.instruction_owner')"
                        />
                    </div>
                </details>
                <div class="border-border bg-surface-panel sticky bottom-0 flex flex-wrap gap-3 border-t py-3">
                    <x-button type="submit" wire:loading.attr="disabled">{{ __('sponsors.save_promise') }}</x-button>
                    <x-button variant="secondary" wire:click="cancelDraft">{{ __('sponsors.cancel_draft') }}</x-button>
                </div>
            </fieldset>
        </form>
    @endif
    @if ($instruction)
        <section class="border-border bg-surface-panel border-y p-4 sm:p-6" data-sponsor-draft>
            <h2 class="mb-5 text-xl font-semibold">
                {{ __('sponsors.review_instruction') }} · {{ $instruction->description }}
            </h2>
            <div class="flex flex-col gap-5">
                <x-form.inputs.select
                    wire:model.live="mode"
                    :label="__('sponsors.destination')"
                    :options="['segment' => __('sponsors.segment'), 'setup' => __('sponsors.setup'), 'remove' => __('sponsors.remove')]"
                    :selected="$mode"
                />
                @if ($mode !== 'remove')
                    <x-form.inputs.select
                        wire:model="targetKey"
                        :label="__('sponsors.target')"
                        :options="collect($mode === 'segment' ? $packData['content']['segments'] : ($packData['content']['sponsor_tasks'] ?? []))->mapWithKeys(fn (array $target): array => [$mode.':'.$target['id'] => $target['label']])->prepend(__('sponsors.new_item'), '')->all()"
                        :selected="$targetKey"
                    />
                    @if ($mode === 'segment')
                        <x-form.inputs.select
                            wire:model="afterMatchId"
                            :label="__('sponsors.position')"
                            :options="collect($packData['card']['rows'])->mapWithKeys(fn (array $match): array => [$match['id'] => $match['number'].' · '.$match['label']])->prepend(__('sponsors.before_card'), '')->all()"
                            :selected="$afterMatchId"
                        />
                    @endif
                @else
                    <p class="text-muted-foreground text-sm">{{ __('sponsors.retract_help') }}</p>
                    <x-form.inputs.textarea wire:model="instructionReason" :label="__('sponsors.reason')" rows="2" />
                @endif
                <div class="flex flex-wrap gap-3">
                    <x-button
                        variant="secondary"
                        wire:click="previewInstruction"
                        x-bind:disabled="! online || requestFailed"
                    >{{ __('sponsors.preview') }}</x-button>
                    <x-button variant="secondary" wire:click="cancelDraft">{{ __('sponsors.cancel_draft') }}</x-button>
                </div>
                @if ($previewHash !== '')
                    <div class="border-outline border p-4" data-instruction-preview>
                        @if (($preview['target'] ?? $preview['linked'] ?? null) !== null)
                            @php($currentInstruction = $preview['target'] ?? $preview['linked'])
                            <h3 class="mb-2 font-semibold">{{ __('sponsors.current_instruction') }}</h3>
                            <p class="font-semibold">{{ $currentInstruction['label'] }}</p>
                            <p class="mb-4 break-words whitespace-pre-wrap">
                                {{ $currentInstruction['notes'] ?? $currentInstruction['instruction'] ?? '' }}
                            </p>
                        @endif
                        <h3 class="mb-2 font-semibold">{{ __('sponsors.proposed_instruction') }}</h3>

                        <p class="text-muted-foreground mb-3 max-w-prose text-sm">{{ __('sponsors.preview_help') }}</p>
                        <p class="font-semibold">{{ $instruction->instruction_label }}</p>
                        <p class="break-words whitespace-pre-wrap">{{ $instruction->instruction }}</p>
                        @if ($instruction->instruction_owner)
                            <p>{{ __('sponsors.task_owner', ['name' => $instruction->instruction_owner]) }}</p>
                        @endif
                        @if ($previewMode === 'remove')
                            <p class="text-warning mt-3">{{ __('sponsors.retract_help') }}</p>
                        @endif
                        @if ($preview !== null && hash_equals($previewHash, $preview['hash']))
                            <x-button
                                class="mt-4"
                                wire:click="saveInstruction"
                                wire:loading.attr="disabled"
                                x-bind:disabled="! online || requestFailed"
                            >{{ __('sponsors.apply') }}</x-button>
                        @else
                            <p class="text-warning mt-4">{{ __('sponsors.preview_changed') }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    @endif
    <div class="max-w-sm">
        <x-form.inputs.select
            class="min-h-11"
            wire:model.live="filter"
            :label="__('sponsors.state')"
            :options="['pending' => __('sponsors.attention'), 'completed' => __('sponsors.completed'), 'cancelled' => __('sponsors.cancelled'), 'all' => __('sponsors.all')]"
            :selected="$filter"
        />
    </div>
    <section class="divide-border divide-y" aria-label="{{ __('sponsors.title') }}">
        @forelse ($rows as $row)
            <article
                wire:key="promise-{{ $row->id }}"
                class="flex min-w-0 flex-col gap-4 py-5 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0">
                    <p class="font-semibold">
                        <a
                            class="underline underline-offset-4"
                            href="{{ route('sponsors.show', $row->sponsor_id) }}"
                        >{{ $row->sponsor_name }}</a>
                        · {{ $row->type->label() }}
                    </p>
                    <h2 class="mt-2 text-lg leading-snug break-words">{{ $row->description }}</h2>
                    <p class="text-muted-foreground mt-2 text-sm">
                        {{ $row->responsible ?? __('sponsors.unassigned') }}
                    </p>
                    <p class="mt-1 text-sm tabular-nums">
                        {{ $row->deadline_at ? __('sponsors.due_at', ['time' => $row->deadline_at->copy()->setTimezone($row->timezone)->format('M j, Y H:i P'), 'zone' => $row->timezone]) : __('sponsors.deadline').': '.__('sponsors.not_set') }}
                    </p>
                    <p class="mt-2 font-semibold {{ $screen->status($row) === __('sponsors.overdue') ? 'text-warning' : '' }}">
                        {{ $screen->status($row) }}
                    </p>
                    @if ($screen->eventNeedsReview($row))
                        <p class="text-warning mt-2 text-sm">{{ __('sponsors.event_review') }}</p>
                    @endif
                    @if ($screen->instructionNeedsReview($row))
                        <p class="text-warning mt-2 text-sm">{{ __('sponsors.pack_review') }}</p>
                    @endif
                    <p class="text-muted-foreground mt-2 text-sm">
                        {{ $row->pack_key === null ? __('sponsors.no_instruction') : (str_starts_with($row->pack_key, 'setup:') ? __('sponsors.setup_linked') : ($screen->liveState($row) ?? __('sponsors.no_live_item'))) }}
                    </p>
                    <details class="mt-3 text-sm">
                        <summary class="min-h-11 cursor-pointer py-3">
                            {{ __('sponsors.private_notes') }} · {{ __('sponsors.evidence') }}
                        </summary>
                        <p class="break-words whitespace-pre-wrap">{{ $row->notes }}</p>
                        <p class="mt-2 break-words">
                            {{ __('sponsors.reference') }}: {{ $row->agreement_reference ?? __('sponsors.not_set') }}
                        </p>
                        <p class="mt-2 break-words whitespace-pre-wrap">{{ $row->evidence_note }}</p>
                        @if ($row->evidence_url)
                            <a
                                href="{{ $row->evidence_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-block min-h-11 py-3 break-all underline"
                            >{{ __('sponsors.evidence_url') }}</a>
                        @endif
                    </details>
                </div>
                <div class="flex shrink-0 flex-wrap gap-3 sm:max-w-52">
                    <x-button
                        variant="secondary"
                        wire:click="edit({{ $row->id }})"
                        x-bind:disabled="! online || requestFailed"
                    >{{ __('sponsors.edit_promise') }}</x-button>
                    @if (! $event->trashed())
                        <x-button
                            variant="secondary"
                            wire:click="beginInstruction({{ $row->id }})"
                        >{{ __('sponsors.review_instruction') }}</x-button>
                    @endif
                    <x-button
                        variant="secondary"
                        wire:click="showHistory({{ $row->id }})"
                    >{{ __('sponsors.show_history') }}</x-button>
                </div>
            </article>
        @empty
            <p class="text-muted-foreground py-6">{{ __('sponsors.empty') }}</p>
        @endforelse
    </section>
    {{ $rows->links() }}
    @if ($history)
        <section class="border-border border-t pt-6">
            <h2 class="text-xl font-semibold">{{ __('sponsors.history') }}</h2>
            <p class="text-muted-foreground mt-2 text-sm">{{ __('sponsors.history_help') }}</p>
            @foreach ($history as $revision)
                <details wire:key="revision-{{ $revision->id }}" class="border-border border-b py-3">
                    <summary class="min-h-11 cursor-pointer py-3">
                        {{ __('sponsors.version') }} {{ $revision->version }} · {{ $revision->created_at->format('M j, Y H:i') }} UTC
                        · {{ $revision->actor_name }}
                    </summary>
                    <p class="break-words whitespace-pre-wrap">{{ $revision->snapshot['description'] ?? '' }}</p>
                    <p>
                        {{ __('sponsors.state') }}: {{ \App\Enums\Events\SponsorCommitmentStatus::tryFrom($revision->snapshot['status'] ?? 'pending')?->label() ?? $revision->snapshot['status'] ?? 'pending' }}
                    </p>
                    <p>
                        {{ __('sponsors.deadline') }}: {{ $revision->snapshot['deadline_at'] ?? __('sponsors.not_set') }}
                    </p>
                    <p class="break-words whitespace-pre-wrap">{{ $revision->snapshot['evidence_note'] ?? '' }}</p>
                    <p class="break-words whitespace-pre-wrap">{{ $revision->reason }}</p>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach (['sponsor_name' => 'sponsor', 'responsible' => 'owner', 'timezone' => 'timezone', 'agreement_reference' => 'agreement', 'notes' => 'private_notes', 'completed_at' => 'completed_at', 'evidence_url' => 'evidence_url', 'instruction_label' => 'instruction_label', 'instruction' => 'instruction', 'instruction_owner' => 'instruction_owner'] as $field => $label)
                            <div class="min-w-0">
                                <dt class="font-semibold">{{ __('sponsors.'.$label) }}</dt>
                                <dd class="break-words whitespace-pre-wrap">
                                    {{ $revision->snapshot[$field] ?? __('sponsors.not_set') }}
                                </dd>
                            </div>
                        @endforeach
                        <div>
                            <dt class="font-semibold">{{ __('sponsors.event') }}</dt>
                            <dd>
                                {{ $revision->snapshot['event_context']['name'] ?? '' }} · {{ $revision->snapshot['event_context']['date'] ?? __('event-pack.unscheduled') }}
                            </dd>
                        </div>
                    </dl>
                </details>
            @endforeach
            {{ $history->links() }}
        </section>
    @endif
</div>
