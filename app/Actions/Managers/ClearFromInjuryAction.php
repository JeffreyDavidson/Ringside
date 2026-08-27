<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Models\Roster\Managers\Manager;
use App\Services\IndividualInjuryService;
use Illuminate\Support\Carbon;

class ClearFromInjuryAction
{
    public function __construct(
        private readonly IndividualInjuryService $injury,
    ) {}

    /**
     * Clear a manager from injury and return them to active management.
     *
     * This handles the complete injury recovery workflow:
     * - Validates the manager can be cleared from injury (currently injured)
     * - Ends the injury period through the shared lifecycle component
     * - Restores the manager to active talent management duties
     * - Makes the manager available for wrestler and tag team assignments again
     * - Preserves injury history for medical and administrative records
     *
     * @param  Manager  $manager  The injured manager to clear
     * @param  Carbon|null  $recoveryDate  The recovery date (defaults to now)
     * @throws CannotBeClearedFromInjuryException When manager cannot be cleared due to business rules
     */
    public function handle(Manager $manager, ?Carbon $recoveryDate = null): void
    {
        $this->injury->clear($manager, $recoveryDate ?? now());
    }
}
