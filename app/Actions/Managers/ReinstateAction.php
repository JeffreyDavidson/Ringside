<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Lifecycle\IndividualSuspensionEligibility;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ReinstateAction
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly IndividualSuspensionEligibility $eligibility,
    ) {}

    /**
     * Reinstate a suspended manager.
     *
     * This handles the complete manager reinstatement workflow:
     * - Validates the manager can be reinstated (currently suspended)
     * - Ends the current suspension period through the shared lifecycle component
     * - Restores the manager to active management duties
     * - Makes the manager available for wrestler/tag team assignments
     *
     * @param  Manager  $manager  The manager to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @throws CannotBeReinstatedException When manager cannot be reinstated due to business rules
     */
    public function handle(Manager $manager, ?Carbon $reinstatementDate = null): void
    {
        $reinstatementDate = DateHelper::resolveDate($reinstatementDate);

        DB::transaction(function () use ($manager, $reinstatementDate): void {
            $lockedManager = Manager::query()->whereKey($manager->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanReinstate($lockedManager);

            $this->suspensionPeriods->end($lockedManager, $reinstatementDate, LifecycleTransitionType::Reinstated);
        });
    }
}
