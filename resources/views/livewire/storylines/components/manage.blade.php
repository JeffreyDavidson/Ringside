<div
    class="mx-auto max-w-6xl space-y-8"
    x-data
    @storyline-form-opened.window="
        $nextTick(() => {
            const form = document.getElementById('storyline-form');
            form?.scrollIntoView({ block: 'start', behavior: 'smooth' });
            form?.querySelector('input,select,textarea')?.focus({ preventScroll: true });
        })
    "
>
    <a
        href="{{ route('storylines.index') }}"
        class="inline-block min-h-11 py-3 underline underline-offset-4"
    >{{ __('storylines.back') }}</a>
    <header class="space-y-3">
        <h1 class="font-display text-3xl leading-tight break-words">{{ $story->title }}</h1>
        <p class="text-muted-foreground">{{ $story->status->label() }} · {{ __('storylines.private') }}</p>
        <p class="max-w-prose whitespace-pre-line">{{ $story->premise }}</p>
        @if ($story->payoff)
            <details>
                <summary class="min-h-11 cursor-pointer py-3 font-semibold">{{ __('storylines.payoff') }}</summary>
                <p class="max-w-prose whitespace-pre-line">{{ $story->payoff }}</p>
            </details>
        @endif
    </header>
    <section class="border-border grid gap-6 border-y py-6 md:grid-cols-2">
        <div>
            <h2 class="text-muted-foreground">{{ __('storylines.last') }}</h2>
            <p class="mt-2 text-xl font-semibold">{{ $last?->heading ?? __('storylines.no_last') }}</p>
            @if ($last)
                <p class="mt-2 max-w-prose whitespace-pre-line">{{ $last->description }}</p>
                <p class="text-muted-foreground mt-2 text-sm">{{ $last->occurred_on->toDateString() }}</p>
            @endif
        </div>
        <div>
            <h2 class="text-muted-foreground">
                {{ __('storylines.next') }}
                @if ($story->status === \App\Enums\Storylines\StorylineStatus::Paused) ·{{ __('storylines.paused') }}@endif
            </h2>
            <p class="mt-2 text-xl font-semibold">{{ $next?->heading ?? __('storylines.no_next') }}</p>
            @if ($next)
                <p class="mt-2 max-w-prose whitespace-pre-line">{{ $next->description }}</p>
                <p class="text-muted-foreground mt-2 text-sm">
                    {{ $next->event?->name }} {{ $next->event?->local_date?->toDateString() ?? $next->target_on?->toDateString() ?? __('storylines.undated') }}
                </p>
            @endif
        </div>
    </section>
    <div class="flex flex-wrap gap-3">
        @if (in_array($story->status, [\App\Enums\Storylines\StorylineStatus::Active, \App\Enums\Storylines\StorylineStatus::Paused], true))
            <x-button wire:click="begin('step')">{{ __('storylines.add_step') }}</x-button>
            <x-button
                variant="secondary"
                wire:click="begin('development')"
            >{{ __('storylines.add_development') }}</x-button>
            <x-button
                variant="secondary"
                wire:click="begin('participant')"
            >{{ __('storylines.add_participant') }}</x-button>
            <x-button variant="secondary" wire:click="begin('story')">{{ __('storylines.edit_story') }}</x-button>
        @endif
        <x-button variant="secondary" wire:click="begin('story_status')">{{ __('storylines.story_status') }}</x-button>
    </div>
    @if ($mode !== null)
        <x-storylines.planning-form
            :$mode
            :$form
            :$editingId
            :$story
            :$participants
            :$events
            :$selectedEvent
            :$source
            :$needsReload
        />
    @endif
    <section>
        <h2 class="font-display mb-4 text-2xl">{{ __('storylines.participants') }}</h2>
        <p class="text-muted-foreground mb-4 text-sm">{{ __('storylines.participants_hint') }}</p>
        <div class="divide-border divide-y">
            @forelse ($participants as $person)
                <div
                    class="flex flex-wrap items-center justify-between gap-3 py-3"
                    wire:key="participant-{{ $person->id }}"
                >
                    <p>
                        {{ $person->label }}
                        @if ($person->role) ·{{ $person->role }}@endif
                        <span class="text-muted-foreground block text-sm"
                            >{{ $person->joined_on->toDateString() }}
                            @if ($person->left_on) —{{ $person->left_on->toDateString() }}·{{ __('storylines.ended') }}@endif
                        </span>
                    </p>
                    @if ($person->left_on === null && in_array($story->status, [\App\Enums\Storylines\StorylineStatus::Active, \App\Enums\Storylines\StorylineStatus::Paused], true))
                        <x-button
                            variant="secondary"
                            wire:click="begin('participant_end', {{ $person->id }})"
                        >{{ __('storylines.end_participation') }}</x-button>
                    @endif
                </div>
            @empty
                <p class="text-muted-foreground">{{ __('storylines.no_participants') }}</p>
            @endforelse
        </div>
    </section>
    <section>
        <h2 class="font-display mb-4 text-2xl">{{ __('storylines.steps') }}</h2>
        <div class="divide-border divide-y">
            @forelse ($steps as $step)
                <article class="py-5" wire:key="step-{{ $step->id }}">
                    <p class="text-muted-foreground mb-2 text-sm">
                        {{ $step->status->label() }} · {{ $step->target_on?->toDateString() }}
                    </p>
                    <h3 class="text-lg font-semibold">{{ $step->heading }}</h3>
                    <p class="mt-2 max-w-prose whitespace-pre-line">{{ $step->description }}</p>
                    <x-storylines.source-links :entry="$step" />
                    @if ($step->event_id)
                        <p class="text-muted-foreground mt-2 text-sm">
                            {{ $sourceStates[$step->id]['context']['segment_label'] ?? $sourceStates[$step->id]['context']['match_label'] ?? '' }} · {{ $sourceStates[$step->id]['context']['live_state'] ? \App\Enums\Events\LiveItemStatus::tryFrom($sourceStates[$step->id]['context']['live_state'])?->label() ?? $sourceStates[$step->id]['context']['live_state'] : __('storylines.no_live') }}
                            @if ($step->match_id) ·{{ $sourceStates[$step->id]['context']['match_result'] ?? __('storylines.no_result') }}@endif
                        </p>
                        @foreach ($sourceStates[$step->id]['warnings'] as $warning)
                            <p class="text-warning mt-2 text-sm">{{ $warning->label() }}</p>
                        @endforeach
                    @endif
                    @if ($step->source_context['event_date'] ?? false)
                        @if ($step->event?->date?->toIso8601String() !== $step->source_context['event_date'])
                            <p class="text-warning mt-2">{{ __('storylines.source_moved') }}</p>
                        @endif
                    @endif
                    @if (in_array($story->status, [\App\Enums\Storylines\StorylineStatus::Active, \App\Enums\Storylines\StorylineStatus::Paused], true) && $step->status !== \App\Enums\Storylines\StepStatus::Happened)
                        <div class="mt-3 flex flex-wrap gap-3">
                            @if ($step->status === \App\Enums\Storylines\StepStatus::Planned)
                                <x-button
                                    variant="secondary"
                                    wire:click="begin('record_step', {{ $step->id }})"
                                >{{ __('storylines.record_step') }}</x-button>
                            @endif
                            <x-button
                                variant="secondary"
                                wire:click="begin('step', {{ $step->id }})"
                            >{{ __('storylines.edit_step') }}</x-button>
                            <x-button
                                variant="secondary"
                                wire:click="begin('step_status', {{ $step->id }})"
                            >{{ __('storylines.step_status') }}</x-button>
                        </div>
                    @endif
                </article>
            @empty
                <p class="text-muted-foreground">{{ __('storylines.no_steps') }}</p>
            @endforelse
        </div>
        {{ $steps->links() }}
    </section>
    <section>
        <h2 class="font-display mb-4 text-2xl">{{ __('storylines.history') }}</h2>
        <div class="divide-border divide-y">
            @forelse ($developments as $development)
                <article class="py-5" wire:key="development-{{ $development->id }}">
                    <p class="text-muted-foreground mb-2 text-sm">
                        {{ $development->occurred_on->toDateString() }} · {{ $development->timezone }}
                        @if ($development->retracted_at) ·{{ __('storylines.retracted') }}@endif
                    </p>
                    <h3 class="text-lg font-semibold">{{ $development->heading }}</h3>
                    <p class="mt-2 max-w-prose whitespace-pre-line">{{ $development->description }}</p>
                    <p class="text-muted-foreground mt-2 text-sm">
                        {{ __('storylines.historical_cast') }}: {{ collect($development->cast)->pluck('label')->join(', ') }}
                    </p>
                    <p class="text-muted-foreground mt-2 text-sm">
                        {{ __('storylines.recorded') }} {{ $development->created_at?->toIso8601String() }}
                    </p>
                    <x-storylines.source-links :entry="$development" />
                    @if (in_array($story->status, [\App\Enums\Storylines\StorylineStatus::Active, \App\Enums\Storylines\StorylineStatus::Paused], true))
                        <div class="mt-3 flex flex-wrap gap-3">
                            <x-button
                                variant="secondary"
                                wire:click="begin('development', {{ $development->id }})"
                            >{{ __('storylines.correct') }}</x-button>
                            @if (! $development->retracted_at)
                                <x-button
                                    variant="destructive"
                                    wire:click="begin('retract', {{ $development->id }})"
                                >{{ __('storylines.retract') }}</x-button>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <p class="text-muted-foreground">{{ __('storylines.no_developments') }}</p>
            @endforelse
        </div>
        {{ $developments->links() }}
    </section>
    <details>
        <summary class="font-display min-h-11 cursor-pointer py-3 text-2xl">{{ __('storylines.changes') }}</summary>
        <div class="divide-border divide-y">
            @foreach ($revisions as $revision)
                <article class="py-4" wire:key="revision-{{ $revision->id }}">
                    <p class="font-semibold">{{ $revision->operation->label() }}</p>
                    <p class="text-muted-foreground mt-2 text-sm">
                        {{ $revision->actor?->full_name }} · {{ $revision->created_at?->toIso8601String() }}
                    </p>
                    @if ($revision->reason)
                        <p class="mt-2 whitespace-pre-line">{{ $revision->reason }}</p>
                    @endif
                    <details class="mt-2">
                        <summary class="min-h-11 cursor-pointer py-3 underline underline-offset-4">
                            {{ __('storylines.view_changes') }}
                        </summary>
                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach (['before', 'after'] as $side)
                                <div>
                                    <h4 class="text-muted-foreground font-semibold">{{ __('storylines.'.$side) }}</h4>
                                    <x-storylines.revision-fields :data="$revision->$side" />
                                </div>
                            @endforeach
                        </div>
                    </details>
                </article>
            @endforeach
        </div>
        {{ $revisions->links() }}
    </details>
</div>
