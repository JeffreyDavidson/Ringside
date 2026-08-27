<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\TagTeams\TagTeamEmploymentService;
use Illuminate\Support\Carbon;

class EmployAction
{
    public function __construct(
        private readonly TagTeamEmploymentService $employment,
        private readonly EmployCurrentWrestlersAction $employCurrentWrestlers,
        private readonly EmployCurrentManagersAction $employCurrentManagers,
    ) {}

    /**
     * Employ a tag team and its eligible members.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $employmentDate = null): void
    {
        $this->employment->employ(
            $tagTeam,
            $employmentDate ?? now(),
            function (TagTeam $lockedTagTeam, Carbon $effectiveDate): void {
                $this->employCurrentWrestlers->handle($lockedTagTeam, $effectiveDate);
                $this->employCurrentManagers->handle($lockedTagTeam, $effectiveDate);
            },
        );
    }
}
