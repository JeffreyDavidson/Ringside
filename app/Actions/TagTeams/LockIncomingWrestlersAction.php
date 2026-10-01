<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Lifecycle\Roster\TagTeams\TagTeamMembershipEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

class LockIncomingWrestlersAction
{
    public function __construct(
        private readonly TagTeamMembershipEligibility $eligibility,
    ) {}

    /**
     * Lock the wrestlers about to join a tag team in ascending id order, then verify none belongs to another tag team.
     *
     * Must run inside the caller's transaction, after the tag team row exists (and is locked when updating).
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function handle(TagTeam $tagTeam, Collection $wrestlers): void
    {
        $lockedWrestlers = Wrestler::query()
            ->whereKey($wrestlers->modelKeys())
            ->inLockOrder()
            ->lockForUpdate()
            ->get();

        $this->eligibility->ensureWrestlersCanJoin($tagTeam, $lockedWrestlers);
    }
}
