<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\SuspensionPeriodManager;
use App\Lifecycle\TagTeamSuspensionEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TagTeamReinstatementService
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
            $lockedTagTeam = TagTeam::query()
                ->whereKey($tagTeam->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanReinstate($lockedTagTeam);
            $this->suspensionPeriods->end($lockedTagTeam, $reinstatementDate, LifecycleTransitionType::Reinstated);
            $afterReinstatement?->__invoke($lockedTagTeam, $reinstatementDate);
        });
    }
}
