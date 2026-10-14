<div class="mx-auto flex w-full max-w-6xl min-w-0 flex-col gap-6 pb-8">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl">{{ __('tournaments.title') }}</h1>
            <p class="text-muted-foreground mt-2">{{ __('tournaments.intro') }}</p>
        </div>
        @can('create', \App\Models\Tournaments\Tournament::class)
            <x-button wire:click="start">{{ __('tournaments.create') }}</x-button>
        @endcan
    </header>
    @error('operation')
        <x-tournaments.error>{{ $message }}</x-tournaments.error>
    @enderror
    @if ($creating)
        <form wire:submit="save" class="border-border flex flex-col gap-4 border-y py-6">
            <h2 class="text-xl font-semibold">{{ __('tournaments.setup') }}</h2>
            <x-tournaments.setup :form="$form" :labels="$labels" :kind="$kind" /><x-button
                type="submit"
                wire:loading.attr="disabled"
                >{{ __('tournaments.save_draft') }}</x-button>
        </form>
    @endif
    <div class="divide-border border-border divide-y border-y">
        @forelse ($tournaments as $tournament)
            <a
                wire:key="tournament-{{ $tournament->id }}"
                href="{{ route('tournaments.show',$tournament) }}"
                class="hover:bg-surface-hover flex min-h-20 items-center justify-between gap-4 py-4"
            >
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold break-words">{{ $tournament->name }}</h2>
                    <p class="text-muted-foreground text-sm">
                        {{ __('tournaments.kind.'.$tournament->entrant_kind->value) }} · {{ __('tournaments.slots',['count'=>$tournament->capacity]) }}
                    </p>
                    <p class="text-muted-foreground mt-1 text-sm">
                        {{ trans_choice('tournaments.entrants_count', $tournament->entries_count) }} · {{ trans_choice('tournaments.results_count', $tournament->recorded_results_count) }}
                    </p>
                </div>
                <span class="shrink-0 text-sm">{{ __('tournaments.status.'.$tournament->status->value) }}</span>
            </a>
        @empty
            <p class="text-muted-foreground py-8">{{ __('tournaments.empty') }}</p>
        @endforelse
    </div>
    {{ $tournaments->links() }}
</div>
