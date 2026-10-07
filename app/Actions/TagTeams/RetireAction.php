<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\CareerPeriodCloser;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Roster\TagTeams\TagTeamRetirementEligibility;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly CareerPeriodCloser $careerPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly TagTeamRetirementEligibility $eligibility,
        private readonly RetireCurrentMembersAction $retireCurrentMembers,
        private readonly ChampionshipReignManager $championshipReigns,
    ) {}

    /**
     * Retire a tag team and its current members.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $retirementDate = null): void
    {
        $effectiveDate = $retirementDate ?? now();

        DB::transaction(function () use ($tagTeam, $effectiveDate): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanRetire($lockedTagTeam);

            $this->careerPeriods->retire($lockedTagTeam, $effectiveDate);

            $this->retirementPeriods->start($lockedTagTeam, $effectiveDate, LifecycleTransitionType::Retired);
            $this->championshipReigns->endCurrentReignsForChampion($lockedTagTeam, $effectiveDate);

            $this->retireCurrentMembers->handle($lockedTagTeam, $effectiveDate);
        });
    }
}
