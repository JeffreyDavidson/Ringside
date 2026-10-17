<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-3">
        <x-form.inputs.text
            wire:model.live.debounce.300ms="search"
            :label="__('storylines.search')"
            size="lg"
            class="min-h-11"
        />
        <x-form.inputs.select
            wire:model.live="status"
            :label="__('storylines.status')"
            size="lg"
            :options="collect(\App\Enums\Storylines\StorylineStatus::cases())->mapWithKeys(fn ($state) => [$state->value => $state->label()])->all()"
            :placeholder="__('storylines.all_status')"
            class="min-h-11"
        />
        <x-form.inputs.text
            wire:model.live.debounce.300ms="participant"
            :label="__('storylines.participant_filter')"
            size="lg"
            class="min-h-11"
        />
    </div>
    <div class="divide-border border-border divide-y border-y">
        @forelse ($stories as $story)
            <article wire:key="story-{{ $story->id }}" class="grid gap-5 py-6 lg:grid-cols-[1.1fr_1fr_1fr]">
                <div class="min-w-0">
                    <a
                        href="{{ route('storylines.show', $story) }}"
                        class="text-xl font-semibold break-words underline underline-offset-4"
                    >{{ $story->title }}</a>
                    <p class="text-muted-foreground mt-2">{{ $story->status->label() }}</p>
                    <p class="mt-2 text-sm">
                        {{ $story->participants->whereNull('left_on')->pluck('label')->join(', ') }}
                    </p>
                </div>
                <div>
                    <h2 class="text-muted-foreground text-sm">{{ __('storylines.last') }}</h2>
                    <p class="mt-2">{{ $story->lastDevelopment?->heading ?? __('storylines.no_last') }}</p>
                    @if ($story->lastDevelopment)
                        <p class="text-muted-foreground mt-1 text-sm">
                            {{ $story->lastDevelopment->occurred_on->toDateString() }}
                        </p>
                    @endif
                </div>
                <div>
                    <h2 class="text-muted-foreground text-sm">
                        {{ __('storylines.next') }}
                        @if ($story->status === \App\Enums\Storylines\StorylineStatus::Paused) ·{{ __('storylines.paused') }}@endif
                    </h2>
                    <p class="mt-2">{{ $story->nextStep?->heading ?? __('storylines.no_next') }}</p>
                    @if ($story->nextStep)
                        <p class="text-muted-foreground mt-1 text-sm">
                            {{ $story->nextStep->event?->name }} {{ $story->nextStep->event?->local_date?->toDateString() ?? $story->nextStep->target_on?->toDateString() ?? __('storylines.undated') }}
                        </p>
                    @endif
                </div>
            </article>
        @empty
            <p class="text-muted-foreground py-8">{{ __('storylines.empty') }}</p>
        @endforelse
    </div>
    {{ $stories->links() }}
</div>
