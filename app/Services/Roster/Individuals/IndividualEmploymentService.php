<?php

declare(strict_types=1);

namespace App\Services\Roster\Individuals;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\IndividualEmploymentEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class IndividualEmploymentService
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(Wrestler|Manager|Referee, Carbon): void|null  $afterEmployment
     */
    public function employ(
        Wrestler|Manager|Referee $individual,
        Carbon $employmentDate,
        ?Closure $afterEmployment = null,
    ): void {
        DB::transaction(function () use ($individual, $employmentDate, $afterEmployment): void {
            $lockedIndividual = $individual->refreshForUpdate();

            $this->eligibility->ensureCanEmploy($lockedIndividual);
            $this->employmentPeriods->start($lockedIndividual, $employmentDate, LifecycleTransitionType::Employed);
            $afterEmployment?->__invoke($lockedIndividual, $employmentDate);
        });
    }
}
