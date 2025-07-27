<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeRetiredException;
use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction
{
    use AsAction;

    /**
     * Retire a referee and end their officiating career.
     *
     * This handles the complete referee retirement workflow:
     * - Validates the referee can be retired (currently employed/active)
     * - Ends suspension and injury if active
     * - Ends employment period if currently employed
     * - Creates retirement record to formally end their officiating career
     * - Makes the referee unavailable for future match assignments
     * - Preserves all historical records and match officiating history
     *
     * @param  Referee  $referee  The referee to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @throws CannotBeRetiredException When referee cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Retire referee immediately
     * RetireAction::run($referee);
     *
     * // Retire with specific date
     * RetireAction::run($referee, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Referee $referee, ?Carbon $retirementDate = null): void
    {
        $referee->ensureCanBeRetired();

        $retirementDate = $retirementDate ?? now();

        DB::transaction(function () use ($referee, $retirementDate): void {
            // Handle referee status - only employed referees can have suspension/injury to end
            if ($referee->isEmployed()) {
                // End suspension or injury if active (employed referee cannot be both)
                if ($referee->isSuspended()) {
                    $currentSuspension = $referee->currentSuspension()->first();
                    if ($currentSuspension) {
                        $currentSuspension->update(['ended_at' => $retirementDate]);
                    }
                } elseif ($referee->isInjured()) {
                    $currentInjury = $referee->currentInjury()->first();
                    if ($currentInjury) {
                        $currentInjury->update(['ended_at' => $retirementDate->toDateTimeString()]);
                    }
                }

                // End employment
                $currentEmployment = $referee->currentEmployment()->first();
                if ($currentEmployment) {
                    $currentEmployment->update(['ended_at' => $retirementDate]);
                    $referee->update(['status' => EmploymentStatus::Retired]);
                }
            }

            $referee->retirements()->create(['started_at' => $retirementDate]);
        });
    }
}
