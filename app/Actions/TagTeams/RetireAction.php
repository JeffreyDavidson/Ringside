<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\TagTeams\TagTeamRetirementService;
use Illuminate\Support\Carbon;

class RetireAction
{
    public function __construct(
        private readonly TagTeamRetirementService $retirement,
        private readonly RetireCurrentMembersAction $retireCurrentMembers,
    ) {}

    /**
     * Retire a tag team and optionally its current members.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $retirementDate = null, bool $retireMembers = true): void
    {
        $this->retirement->retire(
            $tagTeam,
            $retirementDate ?? now(),
            $retireMembers,
            function (TagTeam $lockedTagTeam, Carbon $effectiveDate): void {
                $this->retireCurrentMembers->handle($lockedTagTeam, $effectiveDate);
            },
        );
    }
}
