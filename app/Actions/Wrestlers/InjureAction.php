<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Lifecycle\Periods\InjuryPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualInjuryEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InjureAction
{
    public function __construct(
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly IndividualInjuryEligibility $eligibility,
    ) {}

    /**
     * Injure a wrestler and make them unavailable for competition.
     *
     * This handles the complete wrestler injury workflow:
     * - Validates the wrestler can be injured
     * - Creates the injury period through the shared lifecycle component
     * - Makes the wrestler unavailable for match bookings
     * - May affect tag team bookability if wrestler is in a team
     *
     * @param  Wrestler  $wrestler  The wrestler to injure
     * @param  Carbon|null  $injuryDate  The injury start date (defaults to now)
     * @throws CannotBeInjuredException When wrestler cannot be injured due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $injuryDate = null): void
    {
        $effectiveDate = $injuryDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanInjure($lockedWrestler);
            $this->injuryPeriods->start($lockedWrestler, $effectiveDate, LifecycleTransitionType::Injured);
        });
    }
}
