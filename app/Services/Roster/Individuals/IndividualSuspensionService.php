<?php

declare(strict_types=1);

namespace App\Services\Roster\Individuals;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\IndividualSuspensionEligibility;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class IndividualSuspensionService
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly IndividualSuspensionEligibility $eligibility,
    ) {}

    public function suspend(Wrestler|Manager|Referee $individual, Carbon $suspensionDate): void
    {
        DB::transaction(function () use ($individual, $suspensionDate): void {
            $lockedIndividual = $individual->refreshForUpdate();

            $this->eligibility->ensureCanSuspend($lockedIndividual);
            $this->suspensionPeriods->start($lockedIndividual, $suspensionDate, LifecycleTransitionType::Suspended);
        });
    }

    public function reinstate(Wrestler|Manager|Referee $individual, Carbon $reinstatementDate): void
    {
        DB::transaction(function () use ($individual, $reinstatementDate): void {
            $lockedIndividual = $individual->refreshForUpdate();

            $this->eligibility->ensureCanReinstate($lockedIndividual);
            $this->suspensionPeriods->end($lockedIndividual, $reinstatementDate, LifecycleTransitionType::Reinstated);
        });
    }
}
