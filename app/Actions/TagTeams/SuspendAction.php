<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\Roster\TagTeams\TagTeam;
use App\Services\TagTeamSuspensionService;
use Illuminate\Support\Carbon;

class SuspendAction
{
    public function __construct(
        private readonly TagTeamSuspensionService $suspension,
        private readonly SuspendCurrentMembersAction $suspendCurrentMembers,
    ) {}

    /**
     * Suspend a tag team and its current members.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $suspensionDate = null): void
    {
        $this->suspension->suspend(
            $tagTeam,
            $suspensionDate ?? now(),
            function (TagTeam $lockedTagTeam, Carbon $effectiveDate): void {
                $this->suspendCurrentMembers->handle($lockedTagTeam, $effectiveDate);
            },
        );
    }
}
