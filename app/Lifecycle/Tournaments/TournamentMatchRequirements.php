<?php

declare(strict_types=1);

namespace App\Lifecycle\Tournaments;

use App\Data\Matches\EventMatchData;
use App\Data\Tournaments\TournamentBoutData;
use App\Enums\Tournaments\EntrantKind;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use App\Services\Tournaments\TournamentBracketService;
use App\ValueObjects\BracketShape;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class TournamentMatchRequirements
{
    public function __construct(private TournamentBracketService $bracket) {}

    /** @return list<int> */
    public function permittedPriorMatchIds(?EventMatch $match): array
    {
        if (! $match instanceof EventMatch) {
            return [];
        }
        $link = TournamentMatchLink::query()->with('node', 'tournament')->forMatchId($match->id)->first();
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
                        throw TournamentException::chronology();
                    }
                }
            }
        }
    }

    /** @param Collection<int, covariant \App\Models\Roster\Wrestlers\Wrestler|TagTeam> $competitors */
    public function ensureCompetitors(EventMatch $match, Collection $competitors): void
    {
        $link = TournamentMatchLink::query()->with('node')->forMatchId($match->id)->first();
        if ($link === null) {
            return;
        }
        $bout = $this->bracket->bout($link->node);
        $allowed = collect([$bout->first, $bout->second])->filter()->map(fn (TournamentEntry $entry): string => $entry->competitor_type.':'.$entry->competitor_id)->all();
        if ($link->active_node_id === null || $competitors->contains(fn (Wrestler|TagTeam $competitor): bool => ! in_array($competitor->getMorphClass().':'.$competitor->id, $allowed, true))) {
            throw TournamentException::entrantsChanged();
        }
    }

    public function ensureFinalStakes(EventMatch $match): void
    {
        $link = TournamentMatchLink::query()->with('node', 'tournament')->forMatchId($match->id)->first();
        if ($link !== null && ! new BracketShape($link->tournament->capacity)->isFinal($link->node->round)) {
            throw TournamentException::finalTitlesOnly();
        }
    }

    public function ensureEditable(EventMatch $match, EventMatchData $data): void
    {
        $link = TournamentMatchLink::query()->with('node', 'tournament')->forMatchId($match->id)->first();
        if ($link === null) {
            return;
        }
        if ($data->tournamentVersion !== $link->tournament->version) {
            throw RecordChangedException::changed();
        }
        $this->ensureCompetitors($match, $data->sides->competitors());
        $this->ensureEntrantsMatch($link->tournament->entrant_kind, $this->bracket->bout($link->node), $data);
        if ($data->titles->isNotEmpty()) {
            $this->ensureFinalStakes($match);
        }
    }

    /**
     * Refuse booking a bout whose entrants are not both known and still in, that already has an open booking, that
     * names other entrants, puts a title on a bout before the final, or is not later than the bouts that feed it. The
     * Action checks the version and the tournament status before it calls this.
     *
     * @throws TournamentException
     */
    public function ensureBookable(Tournament $tournament, TournamentNode $node, TournamentBoutData $bout, Event $event, EventMatchData $data): void
    {
        if (! $bout->inputsResolved || ! $bout->first instanceof TournamentEntry || ! $bout->second instanceof TournamentEntry || $bout->first->withdrawn || $bout->second->withdrawn || $bout->complete) {
            throw TournamentException::unresolved();
        }
        if ($bout->link instanceof TournamentMatchLink && $bout->link->match->match_finish === null) {
            throw TournamentException::alreadyBooked();
        }
        $this->ensureEntrantsMatch($tournament->entrant_kind, $bout, $data);
        if ($data->titles->isNotEmpty() && ! new BracketShape($tournament->capacity)->isFinal($node->round)) {
            throw TournamentException::finalTitlesOnly();
        }
        $priorBouts = $this->bracket->bouts($tournament)->only($this->bracket->ancestorNodeIds($node));
        if ($bout->link instanceof TournamentMatchLink) {
            $priorBouts->put($node->id, $bout);
        }
        foreach ($priorBouts as $prior) {
            if ($prior->link === null) {
                continue;
            }
            $priorEvent = $prior->link->match->event;
            if ($priorEvent->id === $event->id && $tournament->repeat_appearances) {
                continue;
            }
            if ($event->date === null || $priorEvent->date === null || $event->date->lessThanOrEqualTo($priorEvent->date)) {
                throw TournamentException::chronology();
            }
        }
    }

    public function ensureDeletable(EventMatch $match): void
    {
        if (TournamentMatchLink::query()->forMatchId($match->id)->exists()) {
            throw TournamentException::linkedDelete();
        }
    }

    public function ensureEventDeletable(Event $event): void
    {
        if (TournamentMatchLink::query()->whereIn('match_id', EventMatch::query()->withoutGlobalScope(PromotionContextScope::class)->withTrashed()->where('event_id', $event->id)->select('events_matches.id'))->whereNotNull('active_node_id')->exists()) {
            throw TournamentException::linkedDelete();
        }
    }

    /** @throws TournamentException */
    private function ensureEntrantsMatch(EntrantKind $kind, TournamentBoutData $bout, EventMatchData $data): void
    {
        $requested = $data->sides->competitors()->map(fn (Wrestler|TagTeam $competitor): string => $competitor->getMorphClass().':'.$competitor->id)->sort()->values()->all();
        $expected = collect([$bout->first, $bout->second])->filter()->map(fn (TournamentEntry $entry): string => $entry->competitor_type.':'.$entry->competitor_id)->sort()->values()->all();
        if ($requested !== $expected || $data->matchType !== $kind->matchType()) {
            throw TournamentException::entrantsChanged();
        }
    }
}
