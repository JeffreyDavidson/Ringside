<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Models\Roster\Managers\Manager;
use App\Services\Roster\Individuals\IndividualEmploymentService;
use Illuminate\Support\Carbon;

class EmployAction
{
    public function __construct(
        private readonly IndividualEmploymentService $employment,
    ) {}

    /**
     * Employ a manager.
     *
     * This handles the complete manager employment workflow:
     * - Validates the manager can be employed (not retired, not already employed)
     * - Creates the employment record through the shared lifecycle component
     * - Makes the manager available for talent management assignments
     *
     * @param  Manager  $manager  The manager to employ
     * @param  Carbon|null  $startDate  The employment start date (defaults to now)
     * @throws CannotBeEmployedException When the manager cannot be employed
     */
    public function handle(Manager $manager, ?Carbon $startDate = null): void
    {
        $this->employment->employ($manager, $startDate ?? now());
    }
}
