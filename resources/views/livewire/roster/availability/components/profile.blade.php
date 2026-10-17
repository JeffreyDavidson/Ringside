<section
    class="border-border bg-surface-panel mb-6 min-w-0 border"
    aria-labelledby="availability-title-{{ $personKind->value }}-{{ $personId }}"
>
    <header class="border-border flex flex-wrap items-center justify-between gap-3 border-b p-5">
        <div>
            <h2
                id="availability-title-{{ $personKind->value }}-{{ $personId }}"
                class="font-display text-foreground text-2xl uppercase"
            >
                {{ __('availability.title') }}
            </h2>
            <p class="text-muted-foreground mt-2 text-sm">{{ __('availability.zone', ['zone' => $zone]) }}</p>
        </div>
        <a
            href="{{ route('availability.index') }}"
            class="text-foreground inline-flex min-h-11 items-center underline underline-offset-4"
        >{{ __('availability.view_calendar') }}</a>
    </header>
    @if ($status !== '')
        <p class="text-success px-5 pt-4" role="status">{{ $status }}</p>
    @endif
    @can('update', $person)
        @if ($person->promotion_id !== null)
            <details wire:ignore.self class="border-border border-b p-5" data-test="availability-add">
                <summary class="text-foreground min-h-11 cursor-pointer font-semibold">
                    {{ __('availability.add') }}
                </summary>
                <form wire:submit="review" class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                for="availability-first"
                                class="text-foreground mb-2 block text-sm font-semibold"
                            >{{ __('availability.first_day') }}</label>
                            <x-form.input
                                id="availability-first"
                                type="date"
                                class="scheme-dark"
                                appearance="ringside"
                                wire:model="startsOn"
                                aria-describedby="availability-errors"
                            />
                        </div>
                        <div>
                            <label
                                for="availability-last"
                                class="text-foreground mb-2 block text-sm font-semibold"
                            >{{ __('availability.last_day') }}</label>
                            <x-form.input
                                id="availability-last"
                                type="date"
                                class="scheme-dark"
                                appearance="ringside"
                                wire:model="endsOn"
                                aria-describedby="availability-errors"
                            />
                        </div>
                    </div>
                    <p class="text-muted-foreground text-sm">{{ __('availability.inclusive_hint') }}</p>
                    <div>
                        <label
                            for="availability-reason"
                            class="text-foreground mb-2 block text-sm font-semibold"
                        >{{ __('availability.reason') }}</label>
                        <x-form.input
                            id="availability-reason"
                            appearance="ringside"
                            wire:model="reason"
                            maxlength="255"
                        />
                        <p class="text-muted-foreground mt-2 text-sm">{{ __('availability.reason_hint') }}</p>
                    </div>
                    <div id="availability-errors">
                        @foreach ($errors->all() as $error)
                            <p class="text-error flex items-start gap-2 text-sm" role="alert">
                                <x-heroicon-s-exclamation-circle
                                    class="size-5 shrink-0"
                                    aria-hidden="true"
                                /><span>{{ $error }}</span>
                            </p>
                        @endforeach
                    </div>
                    <x-buttons.primary type="submit" wire:loading.attr="disabled" wire:target="review">
                        {{ __('availability.review') }}</x-buttons.primary>
                </form>
                @if ($reviewSignature !== null || $savedEntryId !== null)
                    <div class="border-border mt-5 border-t pt-5" data-test="availability-review">
                        <h3 class="text-foreground font-semibold">{{ __('availability.affected') }}</h3>
                        <p class="text-muted-foreground mt-2 text-sm">{{ __('availability.keep_bookings') }}</p>
                        <ul class="mt-3 space-y-2">
                            @forelse ($affected as $event)
                                <li wire:key="availability-affected-{{ $event->id }}">
                                    <a
                                        href="{{ route('events.show', $event) }}"
                                        class="text-foreground inline-flex min-h-11 items-center underline underline-offset-4"
                                    >{{ $event->name }}</a>
                                    <span class="text-muted-foreground text-sm">{{ $event->local_date?->format('M j, Y H:i') ?? __('availability.not_checked') }}</span>
                                </li>
                            @empty
                                <li class="text-muted-foreground text-sm">{{ __('availability.no_affected') }}</li>
                            @endforelse
                        </ul>
                        @if ($reviewSignature !== null)
                            <x-buttons.primary
                                class="mt-4"
                                wire:click="save"
                                wire:loading.attr="disabled"
                                wire:target="save"
                            >
                                {{ __('availability.save') }}</x-buttons.primary>
                        @endif
                    </div>
                @endif
            </details>
        @else
            <p class="text-muted-foreground p-5">{{ __('availability.unowned') }}</p>
        @endif
    @endcan
    <ul class="divide-border divide-y">
        @forelse ($entries as $entry)
            <li
                class="flex flex-wrap items-start justify-between gap-4 p-5"
                wire:key="availability-entry-{{ $entry->id }}"
            >
                <div class="min-w-0">
                    <p class="text-foreground font-semibold">
                        <time>{{ $entry->starts_on }}</time> — <time>{{ $entry->ends_on }}</time>
                    </p>
                    @if ($entry->reason !== null)
                        <p class="text-muted-foreground mt-2 text-sm break-words">{{ $entry->reason }}</p>
                    @endif
                </div>
                @can('update', $person)
                    @if ($cancelId === $entry->id)
                        <div class="space-y-2">
                            <p class="text-foreground text-sm">{{ __('availability.cancel_question') }}</p>
                            <x-buttons.primary wire:click="cancel" wire:loading.attr="disabled">
                                {{ __('availability.confirm_cancel') }}</x-buttons.primary>
                        </div>
                    @else
                        <button
                            type="button"
                            wire:click="requestCancel({{ $entry->id }})"
                            class="text-foreground border-border-strong min-h-11 border px-4 py-2 font-semibold"
                        >
                            {{ __('availability.cancel') }}
                        </button>
                    @endif
                @endcan
            </li>
        @empty
            <li class="text-muted-foreground p-5">{{ __('availability.no_recorded') }}</li>
        @endforelse
    </ul>
    <div class="px-5 pb-4">{{ $entries->links() }}</div>
    <details wire:ignore.self class="border-border border-t p-5">
        <summary class="text-foreground min-h-11 cursor-pointer font-semibold">
            {{ __('availability.history') }}
        </summary>
        <ul class="mt-3 space-y-3">
            @forelse ($history as $entry)
                <li class="text-muted-foreground text-sm break-words" wire:key="availability-history-{{ $entry->id }}">
                    {{ $entry->starts_on }} — {{ $entry->ends_on }} · {{ $entry->cancelled_at ? __('availability.cancelled_label') : __('availability.past') }}
                    @if ($entry->reason) ·{{ $entry->reason }} @endif
                </li>
            @empty
                <li class="text-muted-foreground text-sm">{{ __('availability.no_history') }}</li>
            @endforelse
        </ul>
        {{ $history->links() }}
    </details>
</section>
