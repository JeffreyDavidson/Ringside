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
     * Unretire a tag team, its current members, and return it to employment when it has current wrestlers.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $unretiredDate = null): void
    {
        $effectiveDate = $unretiredDate ?? now();

        DB::transaction(function () use ($tagTeam, $effectiveDate): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanUnretire($lockedTagTeam);
            $this->retirementPeriods->end($lockedTagTeam, $effectiveDate, LifecycleTransitionType::Unretired);

            $this->unretireCurrentMembers->handle($lockedTagTeam, $effectiveDate);

            if (! $lockedTagTeam->currentEmployment()->exists() && $lockedTagTeam->currentWrestlers()->exists()) {
                $this->employ->handle($lockedTagTeam, $effectiveDate);
            }
        });
    }
}
