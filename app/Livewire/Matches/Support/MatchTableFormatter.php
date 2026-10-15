<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Enums\Matches\MatchFinish;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

final readonly class MatchTableFormatter
{
    public function __construct(private MatchCompetitorRouteResolver $routeResolver) {}

    /**
     * Identify a booked roster member across the wrestler, tag team and referee id spaces.
     */
    public static function unbookableKey(Wrestler|TagTeam|Referee $member): string
    {
        return "{$member->getMorphClass()}:{$member->id}";
    }

    /**
     * @param  array<string, true>  $unbookable  Keys from unbookableKey() of booked members who can no longer be booked.
     */
    public function competitorLinks(EventMatch $match, array $unbookable = []): string
    {
        return $match->competitors
            ->competitorModelsBySidePosition()
            ->map(fn (Collection $side): string => $side
                ->map(fn (Wrestler|TagTeam $competitor): string => $this->routeResolver->link(
                    $competitor,
                    isset($unbookable[self::unbookableKey($competitor)]),
                ))
                ->join(' & '))
            ->join(' vs ');
    }

    public function refereeLink(Referee $referee, bool $unbookable = false): string
    {
        $marker = $unbookable ? MatchCompetitorRouteResolver::unbookableMarker() : '';

        return '<a href="'.e(route('referees.show', $referee->id)).'">'.e($referee->full_name).'</a>'.$marker;
    }

    /** Safe retained-history labels even when a legacy competitor reference is missing or foreign. */
    public function historyCompetitorLinks(EventMatch $match): string
    {
        return $match->competitors->toBase()
            ->groupBy(fn (MatchCompetitor $entry): int => $entry->side->position)->sortKeys()
            ->map(fn (Collection $entries): string => $entries
                ->map(fn (MatchCompetitor $entry): string => $this->historyLink($entry, $match->event->promotion_id))
                ->join(' & '))->join(' vs ');
    }

    public function historyResult(EventMatch $match): string
    {
        $rawFinish = $match->getRawOriginal('match_finish');
        $finish = is_string($rawFinish) ? MatchFinish::tryFrom($rawFinish) : null;

        if (! $match->getAttribute('history_recorded') || $finish === null) {
            return e(__($match->getRawOriginal('match_finish') === null ? 'matches.history.result_missing' : 'matches.history.result_incomplete'));
        }

        if ($match->winningSide === null) {
            return e($finish->label());
        }

        return $match->winningSide->competitors
            ->map(fn (MatchCompetitor $entry): string => $this->historyLink($entry, $match->event->promotion_id))
            ->join(' & ').' by '.e($finish->label());
    }

    private function historyLink(MatchCompetitor $entry, ?int $promotionId): string
    {
        $competitor = $entry->getRelation('competitor');

        if ((! $competitor instanceof Wrestler && ! $competitor instanceof TagTeam) || $competitor->promotion_id !== $promotionId) {
            return e(__('matches.history.record_unavailable'));
        }

        return $this->routeResolver->link($competitor).($competitor->trashed() ? ' ('.e(__('matches.history.deleted')).')' : '');
    }

    public function result(EventMatch $match): string
    {
        if ($match->match_finish === null) {
            return 'N/A';
        }

        if ($match->winningSide !== null) {
            $winners = $match->winningSide->competitors
                ->map(fn (MatchCompetitor $competitor): string => $this->routeResolver->link($competitor->competitor))
                ->join(' & ');

            return $winners.' by '.$match->match_finish->label();
        }

        return $match->match_finish->label();
    }
}
