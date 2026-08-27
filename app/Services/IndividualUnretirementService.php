<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\IndividualRetirementEligibility;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class IndividualUnretirementService
{
    public function __construct(
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly IndividualRetirementEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(Wrestler|Manager|Referee, Carbon): void|null  $afterUnretirement
     */
    public function unretire(
        Wrestler|Manager|Referee $individual,
        Carbon $unretirementDate,
        ?Closure $afterUnretirement = null,
    ): void {
        DB::transaction(function () use ($individual, $unretirementDate, $afterUnretirement): void {
            $lockedIndividual = $individual::query()
                ->withTrashed()
                ->whereKey($individual->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanUnretire($lockedIndividual);
            $this->retirementPeriods->end($lockedIndividual, $unretirementDate, LifecycleTransitionType::Unretired);
            $afterUnretirement?->__invoke($lockedIndividual, $unretirementDate);
        });
    }
}
