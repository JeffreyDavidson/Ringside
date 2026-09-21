<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Roster\TagTeams\TagTeamRetirementEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    public function __construct(
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly TagTeamRetirementEligibility $eligibility,
        private readonly UnretireCurrentMembersAction $unretireCurrentMembers,
        private readonly EmployAction $employ,
    ) {}

    /**
     * Unretire a tag team and optionally return it to employment.
     */
    public function handle(
        TagTeam $tagTeam,
        ?Carbon $unretiredDate = null,
        bool $unretireMembers = true,
        bool $employImmediately = true,
        bool $requireAvailablePartners = true
    ): void {
        $effectiveDate = $unretiredDate ?? now();

        DB::transaction(function () use (
            $tagTeam,
            $effectiveDate,
            $unretireMembers,
            $employImmediately,
            $requireAvailablePartners,
        ): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanUnretire($lockedTagTeam, $requireAvailablePartners);
            $this->retirementPeriods->end($lockedTagTeam, $effectiveDate, LifecycleTransitionType::Unretired);

            if ($unretireMembers) {
                $this->unretireCurrentMembers->handle($lockedTagTeam, $effectiveDate);
            }

            if ($employImmediately && ! $lockedTagTeam->currentEmployment()->exists() && $lockedTagTeam->currentWrestlers()->exists()) {
                $this->employ->handle($lockedTagTeam, $effectiveDate);
            }
        });
    }
}
