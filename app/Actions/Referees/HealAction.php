<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Lifecycle\IndividualInjuryEligibility;
use App\Lifecycle\InjuryPeriodManager;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HealAction
{
    public function __construct(
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly IndividualInjuryEligibility $eligibility,
    ) {}

    /**
     * Heal a referee from injury and return them to active officiating.
     *
     * This handles the complete injury recovery workflow:
     * - Validates the referee can be healed from injury (currently injured)
     * - Ends the injury period through the shared lifecycle component
     * - Restores the referee to active officiating status
     * - Makes the referee available for match assignments again
     * - Preserves injury history for medical and administrative records
     *
     * @param  Referee  $referee  The injured referee to heal
     * @param  Carbon|null  $recoveryDate  The recovery date (defaults to now)
     * @throws CannotBeClearedFromInjuryException When referee cannot be healed due to business rules
     */
    public function handle(Referee $referee, ?Carbon $recoveryDate = null): void
    {
        $recoveryDate = DateHelper::resolveDate($recoveryDate);

        DB::transaction(function () use ($referee, $recoveryDate): void {
            $lockedReferee = Referee::query()->lockForUpdate()->findOrFail($referee->getKey());
            $this->eligibility->ensureCanHeal($lockedReferee);

            $this->injuryPeriods->end($lockedReferee, $recoveryDate, LifecycleTransitionType::Healed);
        });
    }
}
