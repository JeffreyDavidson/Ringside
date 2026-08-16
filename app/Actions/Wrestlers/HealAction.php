<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Lifecycle\IndividualInjuryEligibility;
use App\Lifecycle\InjuryPeriodManager;
use App\Models\Roster\Wrestlers\Wrestler;
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
     * Heal a wrestler from injury.
     *
     * This handles the complete injury recovery workflow:
     * - Ends the injury period through the shared lifecycle component
     * - Potentially restores tag team bookability if all members are now available
     *
     * @throws CannotBeClearedFromInjuryException
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        $recoveryDate = DateHelper::resolveDate($recoveryDate);

        DB::transaction(function () use ($wrestler, $recoveryDate): void {
            $lockedWrestler = Wrestler::query()->lockForUpdate()->findOrFail($wrestler->getKey());
            $this->eligibility->ensureCanHeal($lockedWrestler);

            $this->injuryPeriods->end($lockedWrestler, $recoveryDate, LifecycleTransitionType::Healed);
        });

        // which checks if all current wrestlers are available for competition
    }
}
