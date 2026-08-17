<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\TagTeamEmploymentEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly EmployCurrentWrestlersAction $employCurrentWrestlers,
        private readonly EmployCurrentManagersAction $employCurrentManagers,
        private readonly TagTeamEmploymentEligibility $eligibility,
    ) {}

    /**
     * Employ a tag team and its eligible members.
     *
     * This handles the complete tag team employment workflow:
     * - Validates the tag team can be employed (not retired, not already employed)
     * - Creates the employment record through the shared lifecycle component
     * - Employs all current wrestlers through cascading
     * - Employs all current managers through cascading
     * - Makes the tag team available for match bookings and championships
     *
     * @param  TagTeam  $tagTeam  The tag team to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws CannotBeEmployedException When the tag team cannot be employed
     */
    public function handle(TagTeam $tagTeam, ?Carbon $employmentDate = null): void
    {
        $employmentDate = DateHelper::resolveDate($employmentDate);

        DB::transaction(function () use ($tagTeam, $employmentDate): void {
            $lockedTagTeam = TagTeam::query()->whereKey($tagTeam->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanEmploy($lockedTagTeam);

            $this->employmentPeriods->start($lockedTagTeam, $employmentDate, LifecycleTransitionType::Employed);
            $this->employCurrentWrestlers->handle($lockedTagTeam, $employmentDate);
            $this->employCurrentManagers->handle($lockedTagTeam, $employmentDate);
        });
    }
}
