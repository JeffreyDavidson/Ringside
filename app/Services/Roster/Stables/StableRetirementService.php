<?php

declare(strict_types=1);

namespace App\Services\Roster\Stables;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Roster\Stables\StableRetirementEligibility;
use App\Models\Roster\Stables\Stable;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class StableRetirementService
{
    public function __construct(
        private readonly EndActivityPeriodAction $endActivityPeriod,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly StableRetirementEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(Stable, Carbon, Carbon): void|null  $afterRetirement
     */
    public function retire(
        Stable $stable,
        Carbon $retirementDate,
        Carbon $operationalDate,
        ?Closure $afterRetirement = null,
    ): void {
        DB::transaction(function () use ($stable, $retirementDate, $operationalDate, $afterRetirement): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureCanRetire($lockedStable);

            if ($lockedStable->currentActivityPeriod()->exists()) {
                $this->endActivityPeriod->handle($lockedStable, $operationalDate);
            }

            $afterRetirement?->__invoke($lockedStable, $retirementDate, $operationalDate);
            $this->retirementPeriods->start($lockedStable, $retirementDate, LifecycleTransitionType::Retired);
        });
    }
}
