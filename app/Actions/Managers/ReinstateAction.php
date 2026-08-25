<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Models\Roster\Managers\Manager;
use App\Services\IndividualSuspensionService;
use Illuminate\Support\Carbon;

final class ReinstateAction
{
    public function __construct(
        private readonly IndividualSuspensionService $suspension,
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
        $this->suspension->reinstate($manager, $reinstatementDate ?? now());
    }
}
