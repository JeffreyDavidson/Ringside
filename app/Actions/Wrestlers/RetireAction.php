<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

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
     * Retire a wrestler and end their career.
     *
     * This handles the complete wrestler retirement workflow:
     * - Validates the wrestler can be retired
     * - Ends employment, suspension, and injury through lifecycle period managers
     * - Ends all current professional relationships through a typed domain action
     * - Starts a retirement period
     * - Makes the wrestler permanently unavailable for competition
     * - Preserves the operation's transaction boundary
     *
     * @param  Wrestler  $wrestler  The wrestler to retire
     * @param  Carbon|null  $retirementDate  The retirement start date (defaults to now)
     * @throws CannotBeRetiredException When wrestler cannot be retired due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        $retirementDate = $retirementDate ?? now();

        $this->retirement->retire($wrestler, $retirementDate, function (Wrestler|Manager|Referee $lockedWrestler, Carbon $date): void {
            if (! $lockedWrestler instanceof Wrestler) {
                return;
            }

            $this->endCurrentRelationships->handle($lockedWrestler, $date);
        });
    }
}
