<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction
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
        $wrestler->ensureCanBeRetired();

        $retirementDate = $retirementDate ?? now();

        DB::transaction(function () use ($wrestler, $retirementDate): void {
            // End current employment if active
            if ($wrestler->isEmployed()) {
                $currentEmployment = $wrestler->currentEmployment()->first();
                if ($currentEmployment) {
                    $currentEmployment->update(['ended_at' => $retirementDate]);
                    $wrestler->update(['status' => EmploymentStatus::Retired]);
                }
            }

            // End current suspension if active
            if ($wrestler->isSuspended()) {
                $currentSuspension = $wrestler->currentSuspension()->first();
                if ($currentSuspension) {
                    $currentSuspension->update(['ended_at' => $retirementDate]);
                }
            }

            // End current injury if active
            if ($wrestler->isInjured()) {
                $currentInjury = $wrestler->currentInjury()->first();
                if ($currentInjury) {
                    $currentInjury->update(['ended_at' => $retirementDate->toDateTimeString()]);
                }
            }

            // End current tag team partnerships
            $wrestler->tagTeams()->wherePivotNull('left_at')->updateExistingPivot(
                $wrestler->tagTeams()->wherePivotNull('left_at')->pluck('tag_team_id'),
                ['left_at' => $retirementDate]
            );

            // End current stable membership
            $wrestler->stables()->wherePivotNull('left_at')->updateExistingPivot(
                $wrestler->stables()->wherePivotNull('left_at')->pluck('stable_id'),
                ['left_at' => $retirementDate]
            );

            // End current manager relationships
            $wrestler->managers()->wherePivotNull('fired_at')->updateExistingPivot(
                $wrestler->managers()->wherePivotNull('fired_at')->pluck('manager_id'),
                ['fired_at' => $retirementDate]
            );

            // End current championships
            $wrestler->currentChampionships()->update(['lost_at' => $retirementDate]);

            // Create retirement record
            $wrestler->retirements()->create(['started_at' => $retirementDate]);
        });
    }
}
