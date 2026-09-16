<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Lifecycle\Periods\InjuryPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualInjuryEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClearFromInjuryAction
{
    public function __construct(
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly IndividualInjuryEligibility $eligibility,
    ) {}

    /**
     * Clear a wrestler from injury.
     *
     * This handles the complete injury recovery workflow:
     * - Ends the injury period through the shared lifecycle component
     * - Potentially restores tag team bookability if all members are now available
     *
     * @throws CannotBeClearedFromInjuryException
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        $effectiveDate = $recoveryDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanBeClearedFromInjury($lockedWrestler);
            $this->injuryPeriods->end($lockedWrestler, $effectiveDate, LifecycleTransitionType::ClearedFromInjury);
        });
    }
}
