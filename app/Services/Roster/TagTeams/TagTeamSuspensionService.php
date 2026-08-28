<?php

declare(strict_types=1);

namespace App\Services\Roster\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Roster\TagTeams\TagTeamSuspensionEligibility;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Roster\TagTeams\TagTeam;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TagTeamSuspensionService
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly TagTeamSuspensionEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(TagTeam, Carbon): void|null  $afterReinstatement
     */
    public function reinstate(
        TagTeam $tagTeam,
        Carbon $reinstatementDate,
        ?Closure $afterReinstatement = null,
    ): void {
        DB::transaction(function () use ($tagTeam, $reinstatementDate, $afterReinstatement): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanReinstate($lockedTagTeam);
            $this->suspensionPeriods->end($lockedTagTeam, $reinstatementDate, LifecycleTransitionType::Reinstated);
            $afterReinstatement?->__invoke($lockedTagTeam, $reinstatementDate);
        });
    }

    /**
     * @param  Closure(TagTeam, Carbon): void|null  $afterSuspension
     */
    public function suspend(
        TagTeam $tagTeam,
        Carbon $suspensionDate,
        ?Closure $afterSuspension = null,
    ): void {
        DB::transaction(function () use ($tagTeam, $suspensionDate, $afterSuspension): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanSuspend($lockedTagTeam);
            $this->suspensionPeriods->start($lockedTagTeam, $suspensionDate, LifecycleTransitionType::Suspended);
            $afterSuspension?->__invoke($lockedTagTeam, $suspensionDate);
        });
    }
}
