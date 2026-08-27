<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\TagTeams\TagTeamSuspensionService;
use Illuminate\Support\Carbon;

class ReinstateAction
{
    public function __construct(
        private readonly TagTeamSuspensionService $suspension,
        private readonly ReinstateCurrentMembersAction $reinstateCurrentMembers,
    ) {}

    /**
     * Reinstate a suspended tag team and its current members.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $reinstatementDate = null): void
    {
        $this->suspension->reinstate(
            $tagTeam,
            $reinstatementDate ?? now(),
            function (TagTeam $lockedTagTeam, Carbon $effectiveDate): void {
                $this->reinstateCurrentMembers->handle($lockedTagTeam, $effectiveDate);
            },
        );
    }
}
