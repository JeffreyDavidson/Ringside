<?php

declare(strict_types=1);

namespace App\Services\Roster\Individuals;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\IndividualInjuryEligibility;
use App\Lifecycle\InjuryPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class IndividualInjuryService
{
    public function __construct(
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly IndividualInjuryEligibility $eligibility,
    ) {}

    public function injure(Wrestler|Manager|Referee $individual, Carbon $injuryDate): void
    {
        DB::transaction(function () use ($individual, $injuryDate): void {
            $lockedIndividual = $individual::query()
                ->whereKey($individual->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanInjure($lockedIndividual);
            $this->injuryPeriods->start($lockedIndividual, $injuryDate, LifecycleTransitionType::Injured);
        });
    }

    public function clear(Wrestler|Manager|Referee $individual, Carbon $recoveryDate): void
    {
        DB::transaction(function () use ($individual, $recoveryDate): void {
            $lockedIndividual = $individual::query()
                ->whereKey($individual->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanBeClearedFromInjury($lockedIndividual);
            $this->injuryPeriods->end($lockedIndividual, $recoveryDate, LifecycleTransitionType::ClearedFromInjury);
        });
    }
}
