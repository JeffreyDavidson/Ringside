<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly EmployCurrentWrestlersAction $employCurrentWrestlers,
        private readonly EmployCurrentManagersAction $employCurrentManagers,
    ) {}

    /**
     * Employ a tag team and its eligible members.
     *
     * This handles the complete tag team employment workflow:
     * - Validates the tag team can be employed (not retired, not already employed)
     * - Ends retirement if currently retired
     * - Creates the employment record through the shared lifecycle component
     * - Employs all current wrestlers through cascading
     * - Employs all current managers through cascading
     * - Makes the tag team available for match bookings and championships
     *
     * @param  TagTeam  $tagTeam  The tag team to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws Exception When tag team cannot be employed due to business rules
     */
    public function handle(TagTeam $tagTeam, ?Carbon $employmentDate = null): void
    {
        $tagTeam->ensureCanBeEmployed();

        $employmentDate = DateHelper::resolveDate($employmentDate);

        DB::transaction(function () use ($tagTeam, $employmentDate): void {
            if ($tagTeam->isRetired()) {
                $this->retirementPeriods->end($tagTeam, $employmentDate);
            }

            $this->employmentPeriods->start($tagTeam, $employmentDate);
            $this->employCurrentWrestlers->handle($tagTeam, $employmentDate);
            $this->employCurrentManagers->handle($tagTeam, $employmentDate);
        });
    }
}
