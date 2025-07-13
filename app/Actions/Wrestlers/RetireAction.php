<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Retire a wrestler and end their career.
     *
     * This handles the complete wrestler retirement workflow:
     * - Validates the wrestler can be retired (not already retired)
     * - Ends current employment, suspension, and injury if active
     * - Ends current tag team partnerships and stable memberships
     * - Ends current manager relationships
     * - Creates a retirement record with the specified start date
     * - Makes the wrestler permanently unavailable for competition
     *
     * @param  Wrestler  $wrestler  The wrestler to retire
     * @param  Carbon|null  $retirementDate  The retirement start date (defaults to now)
     *
     * @throws CannotBeRetiredException When wrestler cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Retire wrestler immediately
     * RetireAction::run($wrestler);
     *
     * // Retire with specific start date
     * RetireAction::run($wrestler, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        // Validate business rules before proceeding
        $wrestler->ensureCanBeRetired();

        $retirementDate = $this->getEffectiveDate($retirementDate);

        DB::transaction(function () use ($wrestler, $retirementDate): void {
            // End current employment if active
            if ($wrestler->isEmployed()) {
                $this->wrestlerRepository->endEmployment($wrestler, $retirementDate);
            }

            // End current suspension if active
            if ($wrestler->isSuspended()) {
                $this->wrestlerRepository->endSuspension($wrestler, $retirementDate);
            }

            // End current injury if active
            if ($wrestler->isInjured()) {
                $this->wrestlerRepository->endInjury($wrestler, $retirementDate);
            }

            // End current tag team partnerships
            $this->wrestlerRepository->removeFromCurrentTagTeam($wrestler, $retirementDate);

            // End current stable membership
            $this->wrestlerRepository->removeFromCurrentStable($wrestler, $retirementDate);

            // End current manager relationships
            $this->removeCurrentManagers($wrestler, $retirementDate);

            // Create retirement record
            $this->wrestlerRepository->createRetirement($wrestler, $retirementDate);
        });
    }
}
