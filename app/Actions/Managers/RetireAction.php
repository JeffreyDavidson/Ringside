<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\IndividualRetirementService;
use Illuminate\Support\Carbon;

class RetireAction
{
    public function __construct(
        private readonly IndividualRetirementService $retirement,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

    /**
     * Retire a manager and end their management career.
     *
     * This handles the complete manager retirement workflow with cascading effects:
     * - Validates the manager can be retired (currently employed/active)
     * - Ends current management relationships through a typed domain action
     * - Ends suspension, injury, and employment through lifecycle period managers
     * - Starts a retirement period to formally end their management career
     * - Makes the manager unavailable for future talent management
     * - Preserves all historical records and relationships
     *
     * @param  Manager  $manager  The manager to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @throws CannotBeRetiredException When manager cannot be retired due to business rules
     */
    public function handle(Manager $manager, ?Carbon $retirementDate = null): void
    {
        $retirementDate = $retirementDate ?? now();

        $this->retirement->retire($manager, $retirementDate, function (Wrestler|Manager|Referee $lockedManager, Carbon $date): void {
            if (! $lockedManager instanceof Manager) {
                return;
            }

            $this->endCurrentRelationships->handle($lockedManager, $date);
        });
    }
}
