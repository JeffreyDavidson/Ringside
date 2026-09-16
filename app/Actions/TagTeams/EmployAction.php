<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Roster\TagTeams\TagTeamEmploymentEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly TagTeamEmploymentEligibility $eligibility,
        private readonly EmployCurrentWrestlersAction $employCurrentWrestlers,
        private readonly EmployCurrentManagersAction $employCurrentManagers,
    ) {}

    /**
     * Employ a tag team and its eligible members.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $employmentDate = null): void
    {
        $effectiveDate = $employmentDate ?? now();

        DB::transaction(function () use ($tagTeam, $effectiveDate): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanEmploy($lockedTagTeam);
            $this->employmentPeriods->start($lockedTagTeam, $effectiveDate, LifecycleTransitionType::Employed);
            $this->employCurrentWrestlers->handle($lockedTagTeam, $effectiveDate);
            $this->employCurrentManagers->handle($lockedTagTeam, $effectiveDate);
        });
    }
}
