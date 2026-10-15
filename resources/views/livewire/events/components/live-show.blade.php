<div
    x-data="liveShow"
    data-live-show
    data-server-now="{{ now()->utc()->toIso8601String() }}"
    data-current-start="{{ $current['start'] ?? '' }}"
    @if (! $complete) wire:poll.5s.visible="refreshState" @endif
    class="mx-auto flex max-w-6xl flex-col gap-3 pb-8 lg:gap-4"
>
    @php $talent = collect($run?->content['roster'] ?? [])->reject(fn ($person) => str_starts_with($person['key'], 'crew:')); @endphp
    <nav class="flex flex-wrap gap-x-6 gap-y-3 text-sm">
        <a
            href="{{ route('events.show', $event) }}"
            class="min-h-11 py-3 underline underline-offset-4"
        >{{ __('live-show.back') }}</a>
        <a
            href="{{ route('events.pack.show', ['event' => $event, 'print' => 1]) }}"
            class="min-h-11 py-3 underline underline-offset-4"
        >{{ __('live-show.pack') }}</a>
    </nav>
    <header>
        <h1 class="font-display text-2xl leading-tight lg:text-3xl">
            {{ __('live-show.title') }} · {{ $event->name }}
        </h1>
    </header>
    <div data-live-toolbar class="border-border bg-surface sticky -top-4 z-20 border-y lg:-top-7">
        <div class="flex min-h-11 items-center justify-between gap-2 py-1">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 tabular-nums">
                <span class="font-semibold">{{ ($run?->status->label() ?? __('live-show.not_prepared')) }}</span>
                @if ($current)
                    <span
                        aria-label="{{ __('live-show.elapsed') }}"
                        x-text="Math.floor(elapsed / 60) + ':' + String(elapsed % 60).padStart(2, '0')"
                    >0:00</span>
                @endif
                <span wire:loading.remove>
                    <span
                        x-show="online && ! requestFailed"
                        class="text-muted-foreground"
                    >{{ __('live-show.online') }}</span>
                    <span x-show="! online" x-cloak class="text-warning">{{ __('live-show.offline_short') }}</span>
                    <span
                        x-show="online && requestFailed"
                        x-cloak
                        class="text-error"
                    >{{ __('live-show.refresh_needed') }}</span>
                </span>
                <span wire:loading role="status" class="text-muted-foreground">{{ __('live-show.saving') }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @if ($run !== null)
                    @can('viewProduction', $event)
                        <a
                            href="{{ route('events.production-sheet.show', $event) }}"
                            class="flex min-h-11 items-center px-2 font-semibold underline underline-offset-4"
                        >{{ __('production.cues') }}</a>
                    @endcan
                @endif
                <x-button
                    variant="secondary"
                    wire:click="refreshState"
                    wire:loading.attr="disabled"
                    aria-label="{{ __('live-show.refresh_short') }}"
                    title="{{ __('live-show.refresh') }}"
                    class="shrink-0 px-3"
                >
                    <x-heroicon-o-arrow-path class="size-5" />
                    <span class="sr-only">{{ __('live-show.refresh_short') }}</span>
                </x-button>
            </div>
        </div>
        @if ($run !== null)
            <nav aria-label="{{ __('live-show.sections') }}" class="border-border grid grid-cols-3 border-t">
                <a
                    href="#live-show-controls"
                    class="hover:bg-surface-hover flex min-h-14 items-center justify-center px-2 py-2 font-semibold underline underline-offset-4"
                >{{ __('live-show.show_section') }}</a>
                <a
                    href="#live-roster"
                    class="hover:bg-surface-hover flex min-h-14 flex-col items-center justify-center px-2 py-2"
                >
                    <span class="font-semibold underline underline-offset-4">{{ __('live-show.roster_short') }}</span>
                    <span class="text-muted-foreground tabular-nums">{{ __('live-show.arrived_count', ['count' => $talent->whereNotNull('arrived')->count(), 'total' => $talent->count()]) }}</span>
                </a>
                <a
                    href="#live-order"
                    class="hover:bg-surface-hover flex min-h-14 items-center justify-center px-2 py-2 font-semibold underline underline-offset-4"
                >{{ __('live-show.order_short') }}</a>
            </nav>
        @endif
    </div>
    <p x-show="! online" x-cloak role="alert" class="border-warning text-warning border p-4">
        {{ __('live-show.offline') }}
    </p>
    <p x-show="requestFailed" x-cloak role="alert" class="border-error bg-error-surface text-error border p-4">
        <x-heroicon-o-exclamation-triangle class="me-2 inline size-5" />{{ __('live-show.failed') }}
    </p>
    @if ($errors->any())
        <div role="alert" class="border-error bg-error-surface text-error border p-4">
            <x-heroicon-o-exclamation-triangle class="me-2 inline size-5" />
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    @if ($run === null)
        <section class="border-border border p-6">
            <p class="mb-4 max-w-2xl">{{ __('live-show.intro') }}</p>
            @if ($sourceError)
                <p class="text-warning mb-4">{{ $sourceError }}</p>
            @endif
            @can('update', $event)
                <fieldset :disabled="requestFailed || ! online" wire:offline.attr="disabled" @disabled($source === null)>
                    <x-buttons.primary wire:click="prepare" wire:loading.attr="disabled">
                        {{ __('live-show.prepare') }}</x-buttons.primary>
                </fieldset>
            @endcan
        </section>
    @else
        @if ($complete)
            <section id="live-show-controls" class="border-border scroll-mt-36 border p-6">
                <h2 class="text-xl font-semibold">{{ __('live-show.completed_heading') }}</h2>
                <p class="text-muted-foreground mt-2">{{ __('live-show.completed_help') }}</p>
            </section>
        @elseif ($needsReview)
            <section class="border-warning border p-4">
                <p class="text-warning">{{ __('live-show.review_needed') }}</p>
                @if ($sourceError)
                    <p class="mt-2">{{ $sourceError }}</p>
                @endif
                @can('update', $event)
                    <fieldset
                        :disabled="requestFailed || ! online"
                        wire:offline.attr="disabled"
                        @disabled($source === null)
                        class="mt-3"
                    >
                        <x-button
                            variant="secondary"
                            wire:click="beginReview"
                            wire:loading.attr="disabled"
                        >{{ __('live-show.review') }}</x-button>
                    </fieldset>
                @endcan
            </section>
        @endif
        @if ($reviewing)
            <form wire:submit="acceptReview" class="border-border border p-5">
                <h2 class="text-xl font-semibold">{{ __('live-show.review') }}</h2>
                <p class="text-muted-foreground my-3">{{ __('live-show.review_intro') }}</p>
                <ol class="list-inside list-decimal space-y-2">
                    @foreach ($source['content']['items'] ?? [] as $item)
                        <li wire:key="preview-{{ $item['key'] }}">
                            {{ $item['label'] }} · {{ $screen->comparison($item) }}
                            @if ($item['cues'] ?? [])
                                <x-events.production.cues
                                    :cues="$item['cues']"
                                    :assignments="$source['content']['production']['assignments'] ?? []"
                                />
                            @endif
                        </li>
                    @endforeach
                </ol>
                <fieldset
                    :disabled="requestFailed || ! online"
                    wire:offline.attr="disabled"
                    class="mt-4 flex flex-col gap-4"
                >
                    @if ($removed)
                        <h3 class="font-semibold">{{ __('live-show.removed') }}</h3>
                    @endif
                    @foreach ($removed as $item)
                        <label wire:key="removed-{{ $item['key'] }}" class="flex flex-col gap-2"
                            >{{ $item['label'] }}
                            <input
                                wire:model="removedReasons.{{ $item['key'] }}"
                                type="text"
                                maxlength="2000"
                                required
                                class="border-border bg-surface border px-3 py-3"
                            />
                        </label>
                    @endforeach
                    <div class="flex flex-wrap gap-3">
                        <x-buttons.primary type="submit" wire:loading.attr="disabled">
                            {{ __('live-show.adopt') }}</x-buttons.primary
                        ><x-button
                            variant="secondary"
                            type="button"
                            wire:click="cancelDraft"
                            >{{ __('live-show.cancel') }}</x-button>
                    </div>
                </fieldset>
            </form>
        @endif
        @if (! $complete)
            <div id="live-show-controls" class="grid scroll-mt-36 gap-3 lg:grid-cols-[1.3fr_1fr] lg:gap-4">
                <section class="bg-surface-panel p-3 lg:p-6" aria-labelledby="live-now">
                    <h2 id="live-now" class="text-muted-foreground text-base font-semibold lg:text-lg">
                        {{ __('live-show.now') }}
                    </h2>
                    <h3 class="mt-1 text-xl leading-snug font-semibold break-words lg:mt-2 lg:text-2xl">
                        {{ $current['label'] ?? __('live-show.between') }}
                    </h3>
                    @if ($current)
                        <p class="text-muted-foreground mt-1 lg:mt-2">{{ $current['details'] }}</p>
                        <p class="text-muted-foreground mt-1 lg:mt-2">{{ $screen->comparison($current) }}</p>
                        @if ($current['warning'])
                            <p class="text-warning mt-3">{{ __('live-show.unavailable') }}</p>
                        @endif
                        @if ($current['cues'] ?? [])
                            <details>
                                <summary class="min-h-11 cursor-pointer py-3">
                                    {{ __('production.cues') }} ({{ count($current['cues']) }})
                                </summary>
                                <x-events.production.cues
                                    :cues="$current['cues']"
                                    :assignments="$run->content['production']['assignments'] ?? []"
                                />
                            </details>
                        @endif
                        @if ($current['timing']['notes'] ?? null)
                            <p class="mt-4 whitespace-pre-wrap">{{ $current['timing']['notes'] }}</p>
                        @endif
                        @can('update', $event)
                            <fieldset
                                :disabled="requestFailed || ! online"
                                wire:offline.attr="disabled"
                                class="mt-3 lg:mt-4"
                            >
                                <x-buttons.primary
                                    wire:click="item('{{ $current['key'] }}', 'finish')"
                                    wire:loading.attr="disabled"
                                >
                                    {{ __('live-show.finish') }}</x-buttons.primary>
                            </fieldset>
                        @endcan
                    @else
                        <p class="text-muted-foreground mt-2 max-w-lg">{{ __('live-show.no_running') }}</p>
                    @endif
                </section>
                <section class="border-border border p-3 lg:p-6" aria-labelledby="live-next">
                    <h2 id="live-next" class="text-muted-foreground text-base font-semibold lg:text-lg">
                        {{ __('live-show.next') }}
                    </h2>
                    <h3 class="mt-1 text-xl leading-snug font-semibold break-words lg:mt-2 lg:text-2xl">
                        {{ $next['label'] ?? __('live-show.nothing_next') }}
                    </h3>
                    @if ($next)
                        <p class="text-muted-foreground mt-1 lg:mt-2">{{ $next['details'] }}</p>
                        <p class="text-muted-foreground mt-1 lg:mt-2">{{ $screen->comparison($next) }}</p>
                        @if ($next['cues'] ?? [])
                            <details>
                                <summary class="min-h-11 cursor-pointer py-3">
                                    {{ __('production.cues') }} ({{ count($next['cues']) }})
                                </summary>
                                <x-events.production.cues
                                    :cues="$next['cues']"
                                    :assignments="$run->content['production']['assignments'] ?? []"
                                />
                            </details>
                        @endif
                        @if ($next['timing']['notes'] ?? null)
                            <p class="mt-4 whitespace-pre-wrap">{{ $next['timing']['notes'] }}</p>
                        @endif
                        @if ($next['warning'])
                            <p class="text-warning mt-3">{{ __('live-show.unavailable') }}</p>
                        @endif
                        @can('update', $event)
                            <fieldset
                                :disabled="requestFailed || ! online || @js($current !== null || $needsReview)"
                                wire:offline.attr="disabled"
                                @disabled($current !== null || $needsReview)
                                class="mt-3 disabled:opacity-50 lg:mt-4"
                            >
                                <x-buttons.primary
                                    wire:click="item('{{ $next['key'] }}', 'start')"
                                    wire:loading.attr="disabled"
                                >
                                    {{ __('live-show.start') }}</x-buttons.primary>
                            </fieldset>
                        @endcan
                    @endif
                </section>
            </div>
        @endif
        <details class="border-border border-y py-3">
            <summary class="min-h-11 cursor-pointer py-3 font-semibold">{{ __('live-show.details') }}</summary>
            <div class="text-muted-foreground flex flex-col gap-3 pb-3 text-sm">
                <p>
                    {{ $event->local_date?->format('M j, Y · H:i') }}
                    @if ($event->venue) ·{{ $event->venue->name }} @endif
                </p>
                <p>{{ __('live-show.refreshed') }}: {{ $screen->actual($refreshedAt, $run->content['timezone']) }}</p>
                <p>{{ __('live-show.online_help') }}</p>
                <p class="text-muted-foreground text-sm">
                    {{ __('live-show.revision') }}: {{ $run->content['revision'] ?: __('live-show.no_revision') }} · {{ __('live-show.timezone') }}: {{ $run->content['timezone'] }} · {{ __('live-show.saved') }}: {{ $screen->actual($run->updated_at->toIso8601String(), $run->content['timezone']) }}
                </p>
            </div>
        </details>
        @if ($draftKey !== '')
            <form wire:submit="saveCorrection" data-live-draft class="border-border scroll-mt-36 border p-5">
                <h2 class="text-xl font-semibold">{{ __('live-show.'.$draftOperation) }} · {{ $draftLabel }}</h2>
                <fieldset
                    :disabled="requestFailed || ! online"
                    wire:offline.attr="disabled"
                    class="mt-4 flex flex-col gap-4"
                >
                    <label class="flex flex-col gap-2"
                        >{{ __('live-show.reason') }}<input
                            wire:model="reason"
                            type="text"
                            required
                            maxlength="2000"
                            class="border-border bg-surface border px-3 py-3"
                    /></label>
                    @if ($draftOperation === 'correct')
                        <p class="text-muted-foreground text-sm">
                            {{ __('live-show.offset_help') }} · {{ $run->content['timezone'] }}
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="flex min-w-0 flex-col gap-2"
                                >{{ __('live-show.actual_start') }}<input
                                    wire:model="actualStart"
                                    type="datetime-local"
                                    required
                                    class="border-border bg-surface min-w-0 border px-3 py-3"
                            /></label>
                            <label class="flex flex-col gap-2"
                                >{{ __('live-show.start_offset') }}<input
                                    wire:model="startOffset"
                                    type="text"
                                    required
                                    placeholder="-04:00"
                                    class="border-border bg-surface border px-3 py-3"
                            /></label>
                            <label class="flex min-w-0 flex-col gap-2"
                                >{{ __('live-show.actual_finish') }}<input
                                    wire:model="actualFinish"
                                    type="datetime-local"
                                    class="border-border bg-surface min-w-0 border px-3 py-3"
                            /></label>
                            <label class="flex flex-col gap-2"
                                >{{ __('live-show.finish_offset') }}<input
                                    wire:model="finishOffset"
                                    type="text"
                                    required
                                    placeholder="-04:00"
                                    class="border-border bg-surface border px-3 py-3"
                            /></label>
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-3">
                        <x-buttons.primary type="submit" wire:loading.attr="disabled">
                            {{ __('live-show.save_correction') }}</x-buttons.primary
                        ><x-button
                            variant="secondary"
                            type="button"
                            wire:click="cancelDraft"
                            >{{ __('live-show.cancel') }}</x-button>
                    </div>
                </fieldset>
            </form>
        @endif
        <section aria-labelledby="live-roster" class="border-border border-t pt-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 id="live-roster" class="scroll-mt-36 text-xl font-semibold">{{ __('live-show.roster') }}</h2>
                    <p class="text-muted-foreground mt-2 text-sm">{{ __('live-show.roster_help') }}</p>
                    <p class="mt-3 tabular-nums">
                        {{ __('live-show.arrived_count', ['count' => $talent->whereNotNull('arrived')->count(), 'total' => $talent->count()]) }} · {{ __('live-show.briefed_count', ['count' => $talent->whereNotNull('briefed')->count(), 'total' => $talent->count()]) }}
                    </p>
                </div>
                <label class="flex flex-col gap-2 text-sm"
                    >{{ __('live-show.filter') }}
                    <select wire:model.live="rosterFilter" class="border-border bg-surface min-h-11 border px-3">
                        <option value="all">{{ __('live-show.all') }}</option>
                        <option value="arrived">{{ __('live-show.not_arrived') }}</option>
                        <option value="briefed">{{ __('live-show.not_briefed') }}</option>
                    </select>
                </label>
            </div>
            @php $people = $talent->filter(fn ($person) => ! in_array($rosterFilter, ['arrived', 'briefed'], true) || $person[$rosterFilter] === null); @endphp
            <div class="divide-border mt-5 divide-y">
                @forelse ($people as $person)
                    <x-events.production.attendance-person :$person :$event :$run :$complete :$screen />
                @empty
                    <p class="text-muted-foreground py-5">{{ __('live-show.empty_filter') }}</p>
                @endforelse
            </div>
        </section>
        @can('viewProduction', $event)
            <section aria-labelledby="live-crew" class="border-border border-t pt-6">
                <h2 id="live-crew" class="scroll-mt-36 text-xl font-semibold">{{ __('production.directory') }}</h2>
                @php $crew = collect($run->content['roster'])->filter(fn ($person) => str_starts_with($person['key'], 'crew:')); @endphp
                <p class="mt-3">
                    {{ __('live-show.arrived_count', ['count' => $crew->whereNotNull('arrived')->count(), 'total' => $crew->count()]) }}
                </p>
                <div class="divide-border divide-y">
                    @foreach ($crew as $person)
                        <x-events.production.attendance-person :$person :$event :$run :$complete :$screen />
                    @endforeach
                </div>
            </section>
        @endcan
        <section aria-labelledby="live-order" class="border-border border-t pt-6">
            <h2 id="live-order" class="scroll-mt-36 text-xl font-semibold">{{ __('live-show.order') }}</h2>
            <p class="text-muted-foreground mt-2 text-sm">{{ __('live-show.snapshot_help') }}</p>
            <ol class="divide-border mt-5 divide-y">
                @foreach ($run->content['items'] as $item)
                    <li wire:key="item-{{ $item['key'] }}" class="flex flex-col gap-3 py-5">
                        <div class="flex flex-wrap justify-between gap-2">
                            <h3 class="text-lg font-semibold break-words">{{ $item['label'] }}</h3>
                            <span class="font-semibold">{{ \App\Enums\Events\LiveItemStatus::from($item['state'])->label() }}</span>
                        </div>
                        <p class="text-muted-foreground text-sm">
                            {{ $screen->comparison($item) }} · {{ $item['details'] }} · {{ $item['referees'] }}
                        </p>
                        @if ($item['timing']['notes'] ?? null)
                            <p class="whitespace-pre-wrap">{{ $item['timing']['notes'] }}</p>
                        @endif
                        <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm tabular-nums">
                            <p>
                                {{ __('live-show.actual_start') }}: {{ $screen->actual($item['start'], $run->content['timezone']) }}
                            </p>
                            <p>
                                {{ __('live-show.actual_finish') }}: {{ $screen->actual($item['finish'], $run->content['timezone']) }}
                            </p>
                            @if ($item['start'] && $item['finish'])
                                <p>
                                    {{ __('live-show.duration') }}: {{ (int) \Illuminate\Support\Carbon::parse($item['start'])->diffInSeconds(\Illuminate\Support\Carbon::parse($item['finish'])) }} {{ __('live-show.seconds') }}
                                </p>
                            @endif
                        </div>
                        @if ($item['cues'] ?? [])
                            <details>
                                <summary class="min-h-11 cursor-pointer py-3">
                                    {{ __('production.cues') }} ({{ count($item['cues']) }})
                                </summary>
                                <x-events.production.cues
                                    :cues="$item['cues']"
                                    :assignments="$run->content['production']['assignments'] ?? []"
                                />
                            </details>
                        @endif
                        @if ($item['reason'])
                            <p class="text-sm">{{ __('live-show.reason') }}: {{ $item['reason'] }}</p>
                        @endif
                        @if (! $complete)
                            @can('update', $event)
                                <fieldset
                                    :disabled="requestFailed || ! online"
                                    wire:offline.attr="disabled"
                                    class="flex flex-wrap gap-3"
                                >
                                    @if ($item['state'] === 'pending')
                                        <x-button
                                            variant="secondary"
                                            wire:click="beginCorrection('{{ $item['key'] }}', 'skip')"
                                        >{{ __('live-show.skip') }}</x-button>
                                    @endif
                                    @if (in_array($item['state'], ['running', 'finished'], true))
                                        <x-button
                                            variant="secondary"
                                            wire:click="beginCorrection('{{ $item['key'] }}', 'correct')"
                                        >{{ __('live-show.correct') }}</x-button>
                                    @endif
                                    @if (in_array($item['state'], ['finished', 'skipped'], true))
                                        <x-button
                                            variant="secondary"
                                            wire:click="beginCorrection('{{ $item['key'] }}', 'reopen')"
                                        >{{ __('live-show.reopen') }}</x-button>
                                    @endif
                                </fieldset>
                            @endcan
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
        @if (! $complete)
            @can('update', $event)
                <section class="border-border border-t pt-6">
                    <p class="text-muted-foreground mb-4 max-w-2xl text-sm">{{ __('live-show.complete_help') }}</p>
                    <fieldset
                        :disabled="requestFailed || ! online"
                        wire:offline.attr="disabled"
                        @disabled($current !== null || $next !== null || $needsReview)
                    >
                        <x-buttons.primary
                            wire:click="complete"
                            wire:confirm="{{ __('live-show.complete_confirm') }}"
                            wire:loading.attr="disabled"
                        >
                            {{ __('live-show.complete') }}</x-buttons.primary>
                    </fieldset>
                </section>
            @endcan
        @endif
    @endif
</div>
