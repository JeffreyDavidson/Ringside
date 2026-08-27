<?php

declare(strict_types=1);

namespace App\Services\Roster\Individuals;

use App\Lifecycle\IndividualRetirementEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class IndividualRetirementService
{
    public function __construct(
        private readonly IndividualRetirementPeriodService $retirementPeriods,
        private readonly IndividualRetirementEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(Wrestler|Manager|Referee, Carbon): void|null  $afterRetirement
     */
    public function retire(
        Wrestler|Manager|Referee $individual,
        Carbon $retirementDate,
        ?Closure $afterRetirement = null,
    ): void {
        DB::transaction(function () use ($individual, $retirementDate, $afterRetirement): void {
            $lockedIndividual = $individual::query()
                ->whereKey($individual->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanRetire($lockedIndividual);
            $this->retirementPeriods->start($lockedIndividual, $retirementDate);
            $afterRetirement?->__invoke($lockedIndividual, $retirementDate);
        });
    }
}
