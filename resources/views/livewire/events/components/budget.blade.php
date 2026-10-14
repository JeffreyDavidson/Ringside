<div
    x-data="eventBudget"
    data-budget
    data-leave="{{ __('event-budget.leave') }}"
    class="mx-auto flex max-w-6xl flex-col gap-4 pb-10 lg:gap-6"
    @budget-saved="saved = true"
    @budget-draft-opened="saved = false"
>
    <nav>
        <a
            href="{{ route('events.show', $event) }}"
            class="inline-flex min-h-11 items-center underline underline-offset-4"
        >{{ __('event-budget.back') }}</a>
    </nav>
    <header class="space-y-3">
        <h1 class="font-display text-2xl lg:text-3xl">{{ __('event-budget.title') }}</h1>
        <p class="text-lg font-semibold">
            {{ $event->name }}
            <span class="text-muted-foreground font-normal">· {{ $event->local_date?->format('M j, Y') ?? __('event-budget.date_not_set') }} · USD</span>
        </p>
        <p class="text-muted-foreground max-w-3xl leading-relaxed">{{ __('event-budget.intro') }}</p>
        <p class="font-semibold">
            {{ __('event-budget.'.($budget === null ? 'empty' : ($totals['balanced'] ? 'all_balanced' : 'incomplete'))) }}
        </p>
        @if ($budget)
            <p class="text-muted-foreground text-sm">
                {{ __('event-budget.last_saved') }} {{ $budget->updated_at->utc()->format('Y-m-d H:i') }} UTC · {{ __('event-budget.timezone') }} {{ $budget->timezone }}
            </p>
        @endif
    </header>
    @if ($errors->any())
        <div role="alert" class="border-error bg-error-surface text-error border p-4">
            <x-heroicon-o-exclamation-circle class="mb-2 size-5" aria-hidden="true" />
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    <section
        aria-label="{{ __('event-budget.planned_net') }}"
        class="border-border grid grid-cols-2 gap-4 border-b pb-4 lg:pb-6"
    >
        @foreach (['planned_net', 'confirmed_net'] as $key)
            <div>
                <h2 class="text-muted-foreground">{{ __('event-budget.'.$key) }}</h2>
                <p class="mt-2 text-xl font-semibold tabular-nums">
                    {{ \App\ValueObjects\UsdAmount::display($totals[$key]) }}
                </p>
            </div>
        @endforeach
        @if ($totals['missing'])
            <p class="text-warning sm:col-span-2">{{ __('event-budget.missing', ['count' => $totals['missing']]) }}</p>
        @endif
    </section>
    <section aria-labelledby="settlement-heading">
        <h2 id="settlement-heading" class="mb-4 text-lg font-semibold">{{ __('event-budget.still_to_settle') }}</h2>
        <dl class="grid grid-cols-2 gap-x-5 gap-y-4 lg:grid-cols-4">
            @foreach (['to_pay', 'to_collect', 'refund_to_send', 'refund_to_collect'] as $key)
                <div>
                    <dt class="text-muted-foreground">{{ __('event-budget.'.$key) }}</dt>
                    <dd class="mt-1 font-semibold tabular-nums">
                        {{ \App\ValueObjects\UsdAmount::display($totals[$key]) }}
                    </dd>
                </div>
            @endforeach
        </dl>
        <p class="text-muted-foreground mt-4 max-w-3xl text-sm">{{ __('event-budget.balanced_note') }}</p>
    </section>
    <div class="border-border flex flex-wrap items-center gap-3 border-y py-3">
        <x-button
            variant="secondary"
            wire:click="refreshState"
            wire:loading.attr="disabled"
            x-bind:disabled="! online"
        >{{ __('event-budget.refresh') }}</x-button>
        <x-button
            variant="secondary"
            wire:click="showHistory"
            wire:loading.attr="disabled"
            x-bind:disabled="blocked"
        >{{ __('event-budget.history_all') }}</x-button>
        <span wire:loading role="status">{{ __('event-budget.saving') }}</span>
        <span x-show="saved" x-cloak role="status">{{ __('event-budget.notice') }}</span>
        <p x-show="! online" x-cloak role="status" class="text-warning">{{ __('event-budget.offline') }}</p>
        <p x-show="requestFailed" x-cloak role="alert" class="text-error">{{ __('event-budget.request_failed') }}</p>
    </div>
    <details class="border-border border-y py-4">
        <summary class="min-h-11 cursor-pointer font-semibold">{{ __('event-budget.cash_summary') }}</summary>
        <div class="grid gap-4 py-3 sm:grid-cols-3">
            @foreach (['income', 'expense'] as $direction)
                <p>
                    {{ __('event-budget.'.$direction) }}:
                    <span class="tabular-nums">{{ \App\ValueObjects\UsdAmount::display($totals[$direction]['cash']) }}</span>
                </p>
            @endforeach
            <p>
                {{ __('event-budget.cash_net') }}:
                <span class="tabular-nums">{{ \App\ValueObjects\UsdAmount::display($totals['cash_net']) }}</span>
            </p>
        </div>
    </details>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="flex flex-wrap gap-3">
            <x-button
                wire:click="beginLine(null, 'income')"
                wire:loading.attr="disabled"
                x-bind:disabled="blocked"
            >{{ __('event-budget.add_income') }}</x-button>
            <x-button
                variant="secondary"
                wire:click="beginLine"
                wire:loading.attr="disabled"
                x-bind:disabled="blocked"
            >{{ __('event-budget.add_expense') }}</x-button>
        </div>
        <x-form.inputs.select
            class="min-h-11"
            :label="__('event-budget.filter')"
            wire:model.live="filter"
            :options="collect(['all', 'needs_amount', 'to_pay', 'to_collect', 'refunds'])->mapWithKeys(fn ($key) => [$key => __('event-budget.'.$key)])->all()"
        />
    </div>
    @if ($operation !== null)
        <section
            data-budget-draft
            aria-labelledby="budget-draft-heading"
            class="border-outline bg-surface-panel border p-4 lg:p-6"
        >
            <h2 id="budget-draft-heading" class="text-lg font-semibold">
                {{ __('event-budget.'.$operation->value) }}
                @if ($draftLabel) ·{{ $draftLabel }} @endif
            </h2>
            <p class="text-muted-foreground mt-2 text-sm">{{ __('event-budget.manual') }}</p>
            <form method="post" wire:submit="save" @input="dirty = true" class="mt-5 space-y-5">
                @csrf
                @if ($operation === \App\Enums\Events\BudgetOperation::SaveLine)
                    <p class="text-muted-foreground">{{ __('event-budget.amount_help') }}</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-form.inputs.select
                            class="min-h-11"
                            :label="__('event-budget.direction')"
                            wire:model.live="lineForm.direction"
                            :options="['expense' => __('event-budget.expense'), 'income' => __('event-budget.income')]"
                        />
                        <x-form.inputs.select
                            class="min-h-11"
                            :label="__('event-budget.category')"
                            wire:model="lineForm.category"
                            :options="collect($categories)->mapWithKeys(fn ($category) => [$category->value => __('event-budget.'.$category->value)])->all()"
                        />
                        <x-form.inputs.text
                            class="min-h-11"
                            :label="__('event-budget.description')"
                            wire:model="lineForm.description"
                            maxlength="255"
                            required
                        />
                        <x-form.inputs.text
                            class="min-h-11"
                            :label="__('event-budget.counterparty')"
                            wire:model="lineForm.counterparty"
                            maxlength="255"
                            required
                        />
                        <x-form.inputs.text
                            class="min-h-11"
                            :label="__('event-budget.planned').' (USD)'"
                            wire:model="lineForm.planned"
                            inputmode="decimal"
                        />
                        <x-form.inputs.text
                            class="min-h-11"
                            :label="__('event-budget.confirmed').' (USD)'"
                            wire:model="lineForm.confirmed"
                            inputmode="decimal"
                        />
                        <x-form.input
                            class="min-h-11"
                            type="date"
                            :label="__('event-budget.due_date')"
                            wire:model="lineForm.due_date"
                        />
                        <x-form.inputs.text
                            class="min-h-11"
                            :label="__('event-budget.reason')"
                            wire:model="lineForm.reason"
                            maxlength="1000"
                        />
                        <x-form.inputs.textarea
                            :label="__('event-budget.notes')"
                            wire:model="lineForm.notes"
                            maxlength="2000"
                            class="md:col-span-2"
                        />
                    </div>
                    <label class="flex min-h-11 items-center gap-3"
                        ><input
                            type="checkbox"
                            wire:model="lineForm.acknowledge"
                        />{{ __('event-budget.acknowledge') }}</label>
                @else
                    @if ($operation === \App\Enums\Events\BudgetOperation::CorrectMovement)
                        <p>{{ __('event-budget.correction_help') }}</p>
                        <label class="flex min-h-11 items-center gap-3"
                            ><input
                                type="checkbox"
                                wire:model.live="replaceMovement"
                            />{{ __('event-budget.replace') }}</label>
                    @endif
                    @if ($operation === \App\Enums\Events\BudgetOperation::RecordMovement || ($operation === \App\Enums\Events\BudgetOperation::CorrectMovement && $replaceMovement))
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-form.inputs.select
                                class="min-h-11"
                                :label="__('event-budget.movements')"
                                wire:model.live="movementForm.kind"
                                :options="['payment' => __('event-budget.payment'), 'refund' => __('event-budget.refund')]"
                            />
                            <x-form.inputs.text
                                class="min-h-11"
                                :label="__('event-budget.amount')"
                                wire:model="movementForm.amount"
                                inputmode="decimal"
                                required
                            />
                            <x-form.input
                                class="min-h-11"
                                type="date"
                                :label="__('event-budget.effective_date')"
                                wire:model="movementForm.effective_date"
                                required
                            />
                            <x-form.inputs.select
                                class="min-h-11"
                                :label="__('event-budget.method')"
                                wire:model="movementForm.method"
                                :options="['cash' => __('event-budget.cash_method'), 'bank_transfer' => __('event-budget.bank_transfer'), 'other' => __('event-budget.other')]"
                            />
                            @if ($movementForm->kind === 'refund')
                                <p class="text-muted-foreground md:col-span-2">{{ __('event-budget.refund_help') }}</p>
                                <x-form.inputs.select
                                    class="min-h-11"
                                    :label="__('event-budget.source')"
                                    wire:model.number="movementForm.source_id"
                                    :options="($selectedLine?->movements ?? collect())->filter(fn ($movement) => $movement->kind === \App\Enums\Events\BudgetMovementKind::Payment && $movement->voided_at === null)->mapWithKeys(fn ($movement) => [$movement->id => $movement->effective_date->toDateString().' · '.\App\ValueObjects\UsdAmount::display($movement->amount_cents)])->all()"
                                    :placeholder="__('event-budget.choose_source')"
                                />
                            @endif
                            <x-form.inputs.text
                                class="min-h-11"
                                :label="__('event-budget.reference')"
                                wire:model="movementForm.reference"
                                maxlength="255"
                            />
                            <x-form.inputs.textarea
                                :label="__('event-budget.notes')"
                                wire:model="movementForm.notes"
                                maxlength="2000"
                            />
                        </div>
                        <label class="flex min-h-11 items-center gap-3"
                            ><input
                                type="checkbox"
                                wire:model="movementForm.acknowledge"
                            />{{ __('event-budget.acknowledge') }}</label>
                    @endif
                    @if ($operation === \App\Enums\Events\BudgetOperation::ArchiveLine)
                        <p>{{ __('event-budget.archive_help') }}</p>
                    @endif
                    @if ($operation !== \App\Enums\Events\BudgetOperation::RecordMovement)
                        <x-form.inputs.text
                            class="min-h-11"
                            :label="__('event-budget.reason')"
                            wire:model="reason"
                            maxlength="1000"
                            required
                        />
                    @endif
                @endif
                <p x-show="dirty" x-cloak class="text-warning">{{ __('event-budget.dirty') }}</p>
                <p class="text-muted-foreground text-sm">{{ __('event-budget.refresh_help') }}</p>
                <div class="flex flex-wrap gap-3">
                    <x-button
                        type="submit"
                        wire:loading.attr="disabled"
                        x-bind:disabled="blocked"
                        >{{ __('event-budget.save') }}</x-button
                    ><x-button
                        variant="secondary"
                        wire:click="cancelDraft"
                        wire:confirm="{{ __('event-budget.leave') }}"
                        wire:loading.attr="disabled"
                        x-bind:disabled="blocked"
                        >{{ __('event-budget.cancel') }}</x-button>
                </div>
            </form>
        </section>
    @endif
    @foreach (['income', 'expense'] as $direction)
        <section aria-labelledby="budget-{{ $direction }}">
            <h2 id="budget-{{ $direction }}" class="mb-4 text-xl font-semibold">
                {{ __('event-budget.'.$direction) }}
            </h2>
            <dl class="border-border mb-4 grid grid-cols-2 gap-4 border-y py-4">
                @foreach (['planned', 'confirmed'] as $key)
                    <div>
                        <dt class="text-muted-foreground">{{ __('event-budget.'.$key) }}</dt>
                        <dd class="font-semibold tabular-nums">
                            {{ \App\ValueObjects\UsdAmount::display($totals[$direction][$key]) }}
                            @if ($totals[$direction]['missing_'.$key])
                                <p class="text-warning text-sm">
                                    {{ __('event-budget.missing', ['count' => $totals[$direction]['missing_'.$key]]) }}
                                </p>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
            <div class="divide-border divide-y">
                @forelse (array_filter($rows, fn ($row) => $row['line']->direction->value === $direction && ($safeFilter === 'all' || $safeFilter === $row['attention'] || ($safeFilter === 'refunds' && str_starts_with($row['attention'], 'refund_')))) as $row)
                    @php($line = $row['line'])
                    <article wire:key="budget-line-{{ $line->id }}" class="py-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-lg font-semibold break-words">{{ $line->description }}</h3>
                                <p class="text-muted-foreground break-words">
                                    {{ $line->counterparty }} · {{ __('event-budget.'.$line->category->value) }}
                                </p>
                                @if ($line->due_date)
                                    <p class="text-sm">
                                        {{ __('event-budget.due_date') }} {{ $line->due_date->toDateString() }}
                                    </p>
                                @endif
                            </div>
                            <p class="font-semibold">
                                {{ __('event-budget.'.$row['attention']) }}
                                @if ($row['balance'] !== null)
                                    <span class="block tabular-nums">{{ \App\ValueObjects\UsdAmount::display(abs($row['balance'])) }}</span>
                                @endif
                            </p>
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                            @foreach (['planned' => $line->planned_cents, 'confirmed' => $line->confirmed_cents, 'cash' => $row['net'], 'variance' => $row['variance']] as $key => $value)
                                <div>
                                    <dt class="text-muted-foreground text-sm">{{ __('event-budget.'.$key) }}</dt>
                                    <dd class="tabular-nums">{{ \App\ValueObjects\UsdAmount::display($value) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        @if ($line->notes)
                            <p class="text-muted-foreground mt-3 break-words whitespace-pre-wrap">{{ $line->notes }}</p>
                        @endif
                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-button
                                variant="secondary"
                                wire:click="beginLine({{ $line->id }})"
                                x-bind:disabled="blocked"
                                >{{ __('event-budget.edit') }}</x-button
                            ><x-button
                                variant="secondary"
                                wire:click="beginMovement({{ $line->id }})"
                                x-bind:disabled="blocked"
                                >{{ __('event-budget.record_payment') }}</x-button
                            ><x-button
                                variant="secondary"
                                wire:click="beginMovement({{ $line->id }}, 'refund')"
                                x-bind:disabled="blocked"
                                >{{ __('event-budget.record_refund') }}</x-button
                            ><x-button
                                variant="secondary"
                                wire:click="showHistory({{ $line->id }})"
                                x-bind:disabled="blocked"
                                >{{ __('event-budget.history') }}</x-button>
                            @if ($line->movements->isEmpty() && ($line->confirmed_cents === null || $line->confirmed_cents === 0))
                                <x-button
                                    variant="destructive"
                                    wire:click="beginArchive({{ $line->id }})"
                                    x-bind:disabled="blocked"
                                >{{ __('event-budget.archive') }}</x-button>
                            @endif
                        </div>
                        <details class="mt-4">
                            <summary class="min-h-11 cursor-pointer">
                                {{ __('event-budget.movements') }} ({{ $line->movements->count() }})
                            </summary>
                            <div class="space-y-3 py-3">
                                @forelse ($line->movements as $movement)
                                    <div class="border-border border-b border-l-0 pb-3">
                                        <p class="tabular-nums">
                                            {{ $movement->effective_date->toDateString() }} · {{ __('event-budget.'.$movement->kind->value) }} · {{ \App\ValueObjects\UsdAmount::display($movement->amount_cents) }}
                                        </p>
                                        <p class="text-muted-foreground break-words">
                                            {{ $movement->reference }} {{ $movement->notes }}
                                        </p>
                                        @if ($movement->voided_at)
                                            <p>{{ __('event-budget.voided') }} · {{ $movement->void_reason }}</p>
                                        @else
                                            <x-button
                                                variant="secondary"
                                                wire:click="beginMovement({{ $line->id }}, '{{ $movement->kind->value }}', {{ $movement->id }})"
                                                x-bind:disabled="blocked"
                                            >{{ __('event-budget.void') }}</x-button>
                                        @endif
                                    </div>
                                @empty
                                    <p>{{ __('event-budget.no_movements') }}</p>
                                @endforelse
                            </div>
                        </details>
                    </article>
                @empty
                    <p class="text-muted-foreground py-4">{{ __('event-budget.no_lines') }}</p>
                @endforelse
            </div>
        </section>
    @endforeach
    @if ($archived->isNotEmpty())
        <details>
            <summary class="min-h-11 cursor-pointer">
                {{ __('event-budget.archived_heading') }} ({{ $archived->count() }})
            </summary>
            @foreach ($archived as $line)
                <p class="border-border flex flex-wrap items-center gap-3 border-b py-3">
                    {{ $line->description }} · {{ $line->counterparty }}<x-button
                        variant="secondary"
                        wire:click="showHistory({{ $line->id }})"
                        >{{ __('event-budget.history') }}</x-button>
                </p>
            @endforeach
        </details>
    @endif
    @if ($historyOpen)
        <section aria-label="{{ __('event-budget.history') }}" class="border-border border-t pt-5">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold">{{ __('event-budget.history') }}</h2>
                <x-button variant="secondary" wire:click="hideHistory">{{ __('event-budget.history_close') }}</x-button>
            </div>
            @forelse ($history ?? [] as $revision)
                <article class="border-border border-b py-4">
                    <h3 class="font-semibold">
                        {{ __('event-budget.'.$revision->operation->value) }} · {{ $revision->actor_label }}
                    </h3>
                    <p class="text-muted-foreground text-sm">
                        {{ $revision->created_at->utc()->format('Y-m-d H:i') }} UTC · #{{ $revision->version }}
                    </p>
                    @if ($revision->reason)
                        <p class="mt-2">{{ $revision->reason }}</p>
                    @endif
                    <details class="mt-2">
                        <summary class="min-h-11 cursor-pointer">
                            {{ __('event-budget.before') }} / {{ __('event-budget.after') }}
                        </summary>
                        @php($changes = app(\App\ViewModels\EventBudgetViewModel::class)->changes($revision))
                        <dl class="space-y-3 py-3">
                            @foreach ($changes as $change)
                                <div>
                                    <dt class="font-semibold">{{ $change['label'] }}</dt>
                                    <dd class="break-words">
                                        {{ __('event-budget.before') }}: {{ $change['before'] }}
                                    </dd>
                                    <dd class="break-words">{{ __('event-budget.after') }}: {{ $change['after'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </details>
                </article>
            @empty
                <p class="py-4">{{ __('event-budget.no_history') }}</p>
            @endforelse
            @if ($history) {{ $history->links() }}@endif
        </section>
    @endif
</div>
