<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\RetirementCascadeStrategy;
use App\Exceptions\Roster\TagTeams\CannotBeRetiredException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
    ) {}

    /**
     * Retire a tag team and end their partnership.
     *
     * This handles the complete tag team retirement workflow:
     * - Validates the tag team can be retired (business rule compliance)
     * - Ends employment and suspension through lifecycle period managers
     * - Optionally cascades retirement to available partners and managers
     * - Starts a retirement period
     * - Makes the tag team permanently unavailable for competition
     * - Preserves all historical records and championship lineage
     * - Individual members may continue their careers independently
     *
     * @param  TagTeam  $tagTeam  The tag team to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @param  bool  $retirePartners  Whether to retire available partners (default: true)
     * @throws CannotBeRetiredException When tag team cannot be retired due to business rules
     */
    public function handle(TagTeam $tagTeam, ?Carbon $retirementDate = null, bool $retirePartners = true): void
    {
        $tagTeam->ensureCanBeRetired();

        $retirementDate = DateHelper::resolveDate($retirementDate);

        DB::transaction(function () use ($tagTeam, $retirementDate, $retirePartners): void {
            if ($tagTeam->isEmployed()) {
                $this->employmentPeriods->end($tagTeam, $retirementDate);
            }

            if ($tagTeam->isSuspended()) {
                $this->suspensionPeriods->end($tagTeam, $retirementDate);
            }

            $this->retirementPeriods->start($tagTeam, $retirementDate);
            RetirementCascadeStrategy::conditionalMembers($retirePartners)($tagTeam, $retirementDate, 'retire');
        });
    }
}
