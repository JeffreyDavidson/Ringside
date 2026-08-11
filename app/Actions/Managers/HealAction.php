<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Lifecycle\InjuryPeriodManager;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;

class HealAction
{
    public function __construct(private readonly InjuryPeriodManager $injuryPeriods) {}

    /**
     * Heal a manager from injury and return them to active management.
     *
     * This handles the complete injury recovery workflow:
     * - Validates the manager can be healed from injury (currently injured)
     * - Ends the injury period through the shared lifecycle component
     * - Restores the manager to active talent management duties
     * - Makes the manager available for wrestler and tag team assignments again
     * - Preserves injury history for medical and administrative records
     *
     * @param  Manager  $manager  The injured manager to heal
     * @param  Carbon|null  $recoveryDate  The recovery date (defaults to now)
     * @throws CannotBeClearedFromInjuryException When manager cannot be healed due to business rules
     */
    public function handle(Manager $manager, ?Carbon $recoveryDate = null): void
    {
        $manager->ensureCanBeHealed();

        $recoveryDate = DateHelper::resolveDate($recoveryDate);

        $this->injuryPeriods->end($manager, $recoveryDate);
    }
}
