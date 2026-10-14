<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Booking;

use App\Data\Matches\EventMatchData;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
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
        $memberIds = $lockedTeams->flatMap(fn (TagTeam $team): array => $team->currentWrestlers->modelKeys());
        Wrestler::query()->whereKey($wrestlers->pluck('id')->merge($memberIds)->unique())->orderBy('id')->lockForUpdate()->get();
    }

    public function forData(EventMatchData $data): void
    {
        $this->forSides($data->sides, $data->referees);
    }

    /**
     * @param  Collection<int, covariant array{wrestlers?: array<int, Wrestler>, tag_teams?: array<int, TagTeam>}>  $sides
     * @param  Collection<int, Referee>|null  $referees
     */
    public function forSides(Collection $sides, ?Collection $referees = null): void
    {
        $this->lock(
            $referees ?? collect(),
            $sides->flatMap(fn (array $side): array => $side['wrestlers'] ?? []),
            $sides->flatMap(fn (array $side): array => $side['tag_teams'] ?? []),
        );
    }
}
