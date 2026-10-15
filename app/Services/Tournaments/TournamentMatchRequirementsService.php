<?php

declare(strict_types=1);

namespace App\Services\Tournaments;

use App\Data\Matches\EventMatchData;
use App\Data\Tournaments\TournamentBoutData;
use App\Enums\Matches\MatchType;
use App\Enums\Tournaments\EntrantKind;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentMatchLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TournamentMatchRequirementsService
{
    public function __construct(private readonly TournamentBracketService $bracket) {}

    /** @return list<int> */
    public function permittedPriorMatchIds(?EventMatch $match): array
    {
        if (! $match instanceof EventMatch) {
            return [];
        }
        $link = TournamentMatchLink::query()->with('node', 'tournament')->where('match_id', $match->id)->first();
        if ($link === null || $link->active_node_id === null || ! $link->tournament->repeat_appearances) {
            return [];
        }
        $ancestors = $this->bracket->ancestorNodeIds($link->node);

        return array_values(TournamentMatchLink::query()->where('tournament_id', $link->tournament_id)->whereIn('node_id', [...$ancestors, $link->node_id])->whereKeyNot($link->id)->with('match')->get()->filter(
            fn (TournamentMatchLink $prior): bool => ! $prior->match->trashed() && $prior->match->event_id === $match->event_id && $prior->match->match_finish !== null,
        )->values()->map(fn (TournamentMatchLink $prior): int => $prior->match_id)->all());
    }

    public function ensureEventChronology(Event $event, ?Carbon $date): void
    {
        $ids = TournamentMatchLink::query()->whereIn('match_id', EventMatch::query()->withoutGlobalScope(PromotionContextScope::class)->withTrashed()->where('event_id', $event->id)->select('events_matches.id'))->pluck('tournament_id')->unique();
        foreach (Tournament::query()->whereKey($ids)->get() as $tournament) {
            $bouts = $this->bracket->bouts($tournament);
            foreach ($bouts as $bout) {
                if ($bout->link === null || $bout->link->match->trashed()) {
                    continue;
                }
                $priors = $bouts->only($this->bracket->ancestorNodeIds($bout->node))->map(fn (TournamentBoutData $prior): ?TournamentMatchLink => $prior->link)->filter()->merge($bout->node->links->where('attempt', '<', $bout->link->attempt));
                foreach ($priors as $prior) {
                    if ($prior->match->trashed() || $prior->match->match_finish === null) {
                        continue;
                    }
                    $before = $prior->match->event;
                    $after = $bout->link->match->event;
                    if ($before->id !== $event->id && $after->id !== $event->id) {
                        continue;
                    }
                    if ($before->id === $after->id && $tournament->repeat_appearances) {
                        continue;
                    }
                    $first = $before->id === $event->id ? $date : $before->date;
                    $second = $after->id === $event->id ? $date : $after->date;
                    if ($first === null || $second === null || $second->lessThanOrEqualTo($first)) {
                        throw TournamentException::because('chronology');
                    }
                }
            }
        }
    }

    /** @param Collection<int, covariant \App\Models\Roster\Wrestlers\Wrestler|TagTeam> $competitors */
    public function ensureCompetitors(EventMatch $match, Collection $competitors): void
    {
        $link = TournamentMatchLink::query()->with('node')->where('match_id', $match->id)->first();
        if ($link === null) {
            return;
        }
        $bout = $this->bracket->bout($link->node);
        $allowed = collect([$bout->first, $bout->second])->filter()->map(fn (TournamentEntry $entry): string => $entry->competitor_type.':'.$entry->competitor_id)->all();
        if ($link->active_node_id === null || $competitors->contains(fn (Wrestler|TagTeam $competitor): bool => ! in_array($competitor->getMorphClass().':'.$competitor->id, $allowed, true))) {
            throw TournamentException::because('entrants_changed');
        }
    }

    public function ensureFinalStakes(EventMatch $match): void
    {
        $link = TournamentMatchLink::query()->with('node', 'tournament')->where('match_id', $match->id)->first();
        if ($link !== null && $link->node->round !== (int) log($link->tournament->capacity, 2)) {
            throw TournamentException::because('final_titles_only');
        }
    }

    public function ensureEditable(EventMatch $match, EventMatchData $data): void
    {
        $link = TournamentMatchLink::query()->with('node', 'tournament')->where('match_id', $match->id)->first();
        if ($link === null) {
            return;
        }
        if ($data->tournamentVersion !== $link->tournament->version) {
            throw TournamentException::because('stale');
        }
        $this->ensureCompetitors($match, $data->sides->flatMap(fn (array $side): array => array_merge($side['wrestlers'] ?? [], $side['tag_teams'] ?? [])));
        $bout = $this->bracket->bout($link->node);
        $requested = $data->sides->flatMap(fn (array $side): array => array_merge($side['wrestlers'] ?? [], $side['tag_teams'] ?? []))->map(fn (Wrestler|TagTeam $competitor): string => $competitor->getMorphClass().':'.$competitor->id)->sort()->values()->all();
        $expected = collect([$bout->first, $bout->second])->filter()->map(fn (TournamentEntry $entry): string => $entry->competitor_type.':'.$entry->competitor_id)->sort()->values()->all();
        $type = $link->tournament->entrant_kind === EntrantKind::Wrestler ? MatchType::Singles : MatchType::TagTeam;
        if ($requested !== $expected || $data->matchType !== $type) {
            throw TournamentException::because('entrants_changed');
        }
        if ($data->titles->isNotEmpty()) {
            $this->ensureFinalStakes($match);
        }
    }

    public function ensureDeletable(EventMatch $match): void
    {
        if (TournamentMatchLink::query()->where('match_id', $match->id)->exists()) {
            throw TournamentException::because('linked_delete');
        }
    }

    public function ensureEventDeletable(Event $event): void
    {
        if (TournamentMatchLink::query()->whereIn('match_id', EventMatch::query()->withoutGlobalScope(PromotionContextScope::class)->withTrashed()->where('event_id', $event->id)->select('events_matches.id'))->whereNotNull('active_node_id')->exists()) {
            throw TournamentException::because('linked_delete');
        }
    }
}
