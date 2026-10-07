<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster;

use App\Builders\Matches\EventMatchBuilder;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Reads the upcoming or unresulted matches a roster member is booked in, across every promotion.
 */
final readonly class UpcomingBookings
{
    public function exist(Wrestler|Referee|TagTeam $rosterMember): bool
    {
        return $this->matches($rosterMember)->exists();
    }

    /**
     * The events whose cards the member, or a relationship ending with them, is booked on (see relatedMatches()).
     *
     * @return Collection<int, Event>
     */
    public function events(Wrestler|Referee|TagTeam $rosterMember): Collection
    {
        $matches = [$this->matches($rosterMember), ...$this->relatedMatches($rosterMember)];

        return Event::query()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->where(function (Builder $query) use ($matches): void {
                foreach ($matches as $matchesQuery) {
                    $query->orWhereIn('id', $matchesQuery->select('event_id'));
                }
            })
            ->orderBy('date')
            ->orderBy('id')
            ->with('promotion')
            ->get(['id', 'name', 'date', 'promotion_id']);
    }

    /**
     * @return EventMatchBuilder<EventMatch>
     */
    private function matches(Wrestler|Referee|TagTeam $rosterMember): EventMatchBuilder
    {
        return $this->liveMatches()->forRosterMember($rosterMember);
    }

    /**
     * Retiring a tag team retires its current wrestlers, and retiring or releasing a wrestler ends their tag team
     * membership, so bookings of those related members break too. One extra query finds them.
     *
     * @return list<EventMatchBuilder<EventMatch>>
     */
    private function relatedMatches(Wrestler|Referee|TagTeam $rosterMember): array
    {
        if ($rosterMember instanceof TagTeam) {
            return array_values($rosterMember->currentWrestlers()
                ->withoutGlobalScope(PromotionContextScope::class)
                ->get([(new Wrestler)->qualifyColumn('id')])
                ->map(fn (Wrestler $wrestler): EventMatchBuilder => $this->liveMatches()->forWrestlerId($wrestler->id))
                ->all());
        }

        if ($rosterMember instanceof Wrestler) {
            return array_values($rosterMember->currentTagTeam()
                ->withoutGlobalScope(PromotionContextScope::class)
                ->get([(new TagTeam)->qualifyColumn('id')])
                ->map(fn (TagTeam $tagTeam): EventMatchBuilder => $this->liveMatches()->forTagTeamId($tagTeam->id))
                ->all());
        }

        return [];
    }

    /**
     * @return EventMatchBuilder<EventMatch>
     */
    private function liveMatches(): EventMatchBuilder
    {
        return EventMatch::query()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->upcomingOrUnresulted();
    }
}
