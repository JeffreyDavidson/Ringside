<?php

declare(strict_types=1);

namespace App\Queries\Matches;

use App\Enums\Roster\BookableRosterKind;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * The other shows of a day that the chosen wrestlers, tag teams or referees are already booked on, read in
 * one statement for the whole selection. Matches and events that are deleted are ignored, and only shows of
 * the booked show's promotion are named.
 */
class SameDayBookingsQuery
{
    /**
     * A tag team also matches the current members of the chosen teams that are booked on their own, so a team
     * is warned about when any one of its wrestlers is busy.
     *
     * @param  Collection<int, int>  $ids  the chosen ids of the given kind
     * @param  Carbon  $from  the start of the day (inclusive)
     * @param  Carbon  $until  the start of the next day (exclusive)
     * @param  int  $exceptEventId  the show being booked, which is never "another" show
     * @param  int|null  $promotionId  the booked show's promotion; shows of any other promotion are never named
     * @return Collection<int, array{person: string, event: string}>
     */
    public function get(BookableRosterKind $kind, Collection $ids, Carbon $from, Carbon $until, int $exceptEventId, ?int $promotionId): Collection
    {
        if ($ids->isEmpty()) {
            return new Collection;
        }

        $query = match ($kind) {
            BookableRosterKind::Referees => $this->refereeBookings($ids),
            default => $this->competitorBookings($kind, $ids),
        };

        return $query
            ->join('events_matches as m', function (JoinClause $join): void {
                $join->on('m.id', '=', 'b.match_id')->whereNull('m.deleted_at');
            })
            ->join('events as e', function (JoinClause $join): void {
                $join->on('e.id', '=', 'm.event_id')->whereNull('e.deleted_at');
            })
            ->where('e.id', '!=', $exceptEventId)
            ->where('e.promotion_id', $promotionId)
            ->where('e.date', '>=', $from->copy()->utc())
            ->where('e.date', '<', $until->copy()->utc())
            ->addSelect(['e.name as event', 'e.date as event_date', 'e.id as event_id'])
            ->distinct()
            ->orderBy('event_date')
            ->orderBy('person')
            ->orderBy('event_id')
            ->get()
            ->map(fn (stdClass $booking): array => ['person' => (string) $booking->person, 'event' => (string) $booking->event]);
    }

    /** @param  Collection<int, int>  $refereeIds */
    private function refereeBookings(Collection $refereeIds): Builder
    {
        return DB::table('events_matches_referees as b')
            ->join('referees as p', 'p.id', '=', 'b.referee_id')
            ->whereIn('b.referee_id', $refereeIds)
            ->addSelect('p.full_name as person');
    }

    /** @param  Collection<int, int>  $ids */
    private function competitorBookings(BookableRosterKind $kind, Collection $ids): Builder
    {
        $wrestlerType = (new Wrestler)->getMorphClass();
        $tagTeamType = (new TagTeam)->getMorphClass();

        return DB::table('events_matches_competitors as b')
            ->leftJoin('wrestlers as w', function (JoinClause $join) use ($wrestlerType): void {
                $join->on('w.id', '=', 'b.competitor_id')->where('b.competitor_type', $wrestlerType);
            })
            ->leftJoin('tag_teams as t', function (JoinClause $join) use ($tagTeamType): void {
                $join->on('t.id', '=', 'b.competitor_id')->where('b.competitor_type', $tagTeamType);
            })
            ->where(function (Builder $competitors) use ($kind, $ids, $wrestlerType, $tagTeamType): void {
                if ($kind === BookableRosterKind::Wrestlers) {
                    $competitors->where('b.competitor_type', $wrestlerType)->whereIn('b.competitor_id', $ids);

                    return;
                }

                $competitors
                    ->where(fn (Builder $team): Builder => $team->where('b.competitor_type', $tagTeamType)->whereIn('b.competitor_id', $ids))
                    ->orWhere(fn (Builder $member): Builder => $member->where('b.competitor_type', $wrestlerType)->whereIn(
                        'b.competitor_id',
                        DB::table('tag_teams_wrestlers')->whereNull('left_at')->whereIn('tag_team_id', $ids)->select('wrestler_id'),
                    ));
            })
            ->addSelect(DB::raw('coalesce(w.name, t.name) as person'));
    }
}
