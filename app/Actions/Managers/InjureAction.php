<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\CannotBeInjuredException;
use App\Lifecycle\InjuryPeriodManager;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;

class InjureAction
{
    public function __construct(private readonly InjuryPeriodManager $injuryPeriods) {}

    /**
     * Record a manager injury.
     *
     * This handles the complete manager injury workflow:
     * - Validates the manager can be injured (currently employed, not already injured)
     * - Creates the injury period through the shared lifecycle component
     * - Temporarily removes the manager from active wrestler/tag team management duties
     * - Maintains employment status while marking as unavailable due to injury
     *
     * @param  Manager  $manager  The manager to mark as injured
     * @param  Carbon|null  $injureDate  The injury date (defaults to now)
     * @throws CannotBeInjuredException When manager cannot be injured due to business rules
     */
    public function handle(Manager $manager, ?Carbon $injureDate = null): void
    {
        $manager->ensureCanBeInjured();

        $injureDate = DateHelper::resolveDate($injureDate);

        $this->injuryPeriods->start($manager, $injureDate);
    }
}
