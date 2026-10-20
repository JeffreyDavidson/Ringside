<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Booking;

use App\Data\Matches\EventMatchData;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ValueObjects\MatchSides;
use Illuminate\Support\Collection;

final class MatchRosterLock
{
    /**
     * Lock team parents before represented members, consistently across all sides.
     *
     * @param  Collection<int, Referee>  $referees
     * @param  Collection<int, Wrestler>  $wrestlers
     * @param  Collection<int, TagTeam>  $teams
     */
    public function lock(Collection $referees, Collection $wrestlers, Collection $teams): void
    {
        Referee::query()->whereKey($referees->pluck('id'))->orderBy('id')->lockForUpdate()->get();
        $lockedTeams = TagTeam::query()->whereKey($teams->pluck('id'))->orderBy('id')->lockForUpdate()->get();
        $lockedTeams->load('currentWrestlers');
        $memberIds = $lockedTeams->currentWrestlers()->modelKeys();
        Wrestler::query()->whereKey($wrestlers->pluck('id')->merge($memberIds)->unique())->orderBy('id')->lockForUpdate()->get();
    }

    public function forData(EventMatchData $data): void
    {
        $this->forSides($data->sides, $data->referees);
    }

    /**
     * @param  Collection<int, Referee>|null  $referees
     */
    public function forSides(MatchSides $sides, ?Collection $referees = null): void
    {
        $this->lock($referees ?? collect(), $sides->wrestlers(), $sides->tagTeams());
    }
}
