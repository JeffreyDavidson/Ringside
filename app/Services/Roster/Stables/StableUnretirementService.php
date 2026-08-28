<?php

declare(strict_types=1);

namespace App\Services\Roster\Stables;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\StableRetirementEligibility;
use App\Models\Roster\Stables\Stable;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class StableUnretirementService
{
    public function __construct(
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly StableRetirementEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(Stable, Carbon): void|null  $afterUnretirement
     */
    public function unretire(
        Stable $stable,
        Carbon $unretirementDate,
        bool $requireFormerMembers = true,
        ?Closure $afterUnretirement = null,
    ): void {
        DB::transaction(function () use ($stable, $unretirementDate, $requireFormerMembers, $afterUnretirement): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureCanUnretire($lockedStable, $requireFormerMembers);
            $this->retirementPeriods->end($lockedStable, $unretirementDate, LifecycleTransitionType::Unretired);
            $afterUnretirement?->__invoke($lockedStable, $unretirementDate);
        });
    }
}
