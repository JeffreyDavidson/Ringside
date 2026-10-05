<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
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
     * @return Collection<int, Event>
     */
    public function events(Wrestler|Referee|TagTeam $rosterMember): Collection
    {
        return Event::query()
            ->withoutGlobalScope('promotion_context')
            ->whereIn('id', $this->matches($rosterMember)->select('event_id'))
            ->orderBy('date')
            ->orderBy('id')
            ->get(['id', 'name', 'date']);
    }

    /**
     * @return Builder<EventMatch>
     */
    private function matches(Wrestler|Referee|TagTeam $rosterMember): Builder
    {
        return EventMatch::query()
            ->withoutGlobalScope('promotion_context')
            ->forRosterMember($rosterMember)
            ->upcomingOrUnresulted();
    }
}
