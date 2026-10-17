@props(['bout', 'tournament', 'availableIds'])
<article
    wire:key="bout-{{ $bout->node->id }}"
    data-bracket-node="{{ $bout->node->id }}"
    data-bracket-parent="{{ $tournament->nodes->first(fn($node)=>$node->round===$bout->node->round+1 && $node->position===(int)ceil($bout->node->position/2))?->id }}"
    class="border-border bg-surface-panel flex w-full min-w-0 flex-col gap-3 border p-4"
>
    <h3 class="text-muted-foreground text-sm font-semibold">
        {{ __('tournaments.bout_label',['round'=>$bout->node->round,'number'=>$bout->node->position]) }}
    </h3>
    <div class="flex flex-col gap-2">
        @foreach ([$bout->first, $bout->second] as $offset => $entry)
            <p class="break-words font-semibold @if($entry && $bout->winner?->id === $entry->id) text-success @endif">
                {{ $entry?->competitor->name ?? ($bout->inputsResolved ? __('tournaments.bye') : __('tournaments.source_winner',['round'=>$bout->node->round-1,'number'=>$bout->node->position*2-1+$offset])) }}
                @if ($entry?->withdrawn)
                    <span class="text-warning text-sm">{{ __('tournaments.withdrawn') }}</span>
                @endif
                @if (! $bout->complete && $entry && ! $entry->withdrawn && ! in_array($entry->competitor_id, $availableIds, true))
                    <span class="text-warning block text-sm">{{ __('tournaments.unavailable') }}</span>
                @endif
                @if ($entry && $bout->winner?->id === $entry->id)
                    <span class="text-sm"
                        ><x-heroicon-o-check class="inline size-4" aria-hidden="true" />
                        {{ __('tournaments.advanced') }}</span>
                @endif
            </p>
        @endforeach
    </div>
    @if ($bout->complete && $bout->winner === null)
        <p class="text-muted-foreground text-sm">{{ __('tournaments.empty_position') }}</p>
    @elseif ($bout->node->walkover_entry_id)
        <p class="text-sm">{{ __('tournaments.walkover') }} · {{ $bout->node->walkover_reason }}</p>
    @elseif ($bout->bye)
        <p class="text-muted-foreground text-sm">{{ __('tournaments.bye') }}</p>
    @elseif ($bout->link)
        <div class="text-sm">
            <a
                href="{{ route('events.show',$bout->link->match->event_id) }}"
                class="underline underline-offset-4"
            >{{ $bout->link->match->event->name }}</a>
            @if ($bout->link->match->event->date)
                <p class="text-muted-foreground mt-1">
                    {{ \App\Services\Promotions\PromotionTimeZoneService::toLocalTime($tournament->promotion, $bout->link->match->event->date)->format('M j, Y · g:i A T') }}
                </p>
            @endif
            <p class="mt-1">{{ $bout->link->match->match_finish?->label() ?? __('tournaments.awaiting_result') }}</p>
        </div>
    @elseif ($bout->inputsResolved && ($bout->first?->withdrawn || $bout->second?->withdrawn || ($bout->first && ! in_array($bout->first->competitor_id, $availableIds, true)) || ($bout->second && ! in_array($bout->second->competitor_id, $availableIds, true))))
        <p class="text-warning text-sm">{{ __('tournaments.booking_blocked') }}</p>
    @elseif ($bout->node->plannedEvent)
        <p class="text-muted-foreground text-sm">
            {{ __('tournaments.planned') }} · {{ $bout->node->plannedEvent->name }}
        </p>
    @else
        <p class="text-muted-foreground text-sm">
            {{ $bout->inputsResolved ? __('tournaments.ready') : __('tournaments.waiting_qualifiers') }}
        </p>
    @endif
    @can('update', $tournament)
        @if (in_array($tournament->status, [\App\Enums\Tournaments\TournamentStatus::Draft, \App\Enums\Tournaments\TournamentStatus::Active], true) && ! $bout->complete)
            <div class="flex flex-wrap gap-2">
                @if ($bout->inputsResolved && $bout->first && $bout->second && ! $bout->first->withdrawn && ! $bout->second->withdrawn && in_array($bout->first->competitor_id, $availableIds, true) && in_array($bout->second->competitor_id, $availableIds, true) && (! $bout->link || $bout->link->match->match_finish))
                    <x-button wire:click="begin('book',{{ $bout->node->id }})">{{ $bout->link ? __('tournaments.rematch') : __('tournaments.book') }}</x-button>
                @endif
                @if ($bout->link)
                    <x-button
                        variant="secondary"
                        wire:click="$dispatch('openModal',{component:'matches.modals.result-modal',arguments:{matchId:{{ $bout->link->match_id }}}})"
                    >{{ $bout->link->match->match_finish ? __('tournaments.correct_result') : __('tournaments.record_result') }}</x-button>
                @else
                    <x-button
                        variant="secondary"
                        wire:click="begin('plan',{{ $bout->node->id }})"
                    >{{ __('tournaments.plan_event') }}</x-button>
                @endif
                @if ($bout->inputsResolved && $bout->first && $bout->second && $bout->first->withdrawn !== $bout->second->withdrawn && ! $bout->link)
                    <x-button
                        variant="secondary"
                        wire:click="begin('walkover',{{ $bout->node->id }})"
                    >{{ __('tournaments.award_walkover') }}</x-button>
                @endif
                @if ($bout->link && ! $bout->link->match->match_finish)
                    <x-button
                        variant="destructive"
                        wire:click="begin('unbook',{{ $bout->node->id }})"
                    >{{ __('tournaments.remove_booking') }}</x-button>
                @endif
            </div>
        @endif
        @if ($bout->complete && $bout->link && $tournament->status !== \App\Enums\Tournaments\TournamentStatus::Cancelled)
            <div class="flex flex-wrap gap-2">
                <x-button
                    variant="secondary"
                    wire:click="$dispatch('openModal',{component:'matches.modals.result-modal',arguments:{matchId:{{ $bout->link->match_id }}}})"
                    >{{ __('tournaments.correct_result') }}</x-button
                ><x-button
                    variant="secondary"
                    wire:click="begin('reset',{{ $bout->node->id }})"
                    >{{ __('tournaments.reset_future') }}</x-button>
            </div>
        @endif
    @endcan
    @if ($bout->node->links->count() > 1 || $bout->node->links->contains(fn ($link) => $link->active_node_id === null))
        <details>
            <summary class="min-h-11 cursor-pointer text-sm">{{ __('tournaments.history') }}</summary>
            <ol class="text-muted-foreground flex flex-col gap-2 text-sm">
                @foreach ($bout->node->links as $link)
                    <li>
                        {{ __('tournaments.attempt',['number'=>$link->attempt]) }} · {{ $link->match->event->name }} · {{ $link->match->match_finish?->label() ?? __('tournaments.remove_unplayed') }}
                        @if ($link->active_node_id === null) ({{ __('tournaments.historical') }})@endif
                    </li>
                @endforeach
            </ol>
        </details>
    @endif
</article>
