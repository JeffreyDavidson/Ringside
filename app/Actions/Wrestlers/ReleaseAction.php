<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeReleasedException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction
{
    use AsAction;

    /**
     * Release a wrestler from employment and end all current relationships.
     *
     * This handles the complete wrestler release workflow with cascading effects:
     * - Validates the wrestler can be released (currently employed)
     * - Ends current tag team partnerships (may affect team bookability)
     * - Ends current stable membership (may affect stable minimum requirements)
     * - Ends current manager relationships
     * - Ends suspension and injury if active
     * - Ends employment period with the specified date
     * - Maintains all historical records for tracking purposes
     *
     * @param  Wrestler  $wrestler  The wrestler to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When wrestler cannot be released due to business rules
     *
     * @example
     * ```php
     * // Release wrestler immediately
     * ReleaseAction::run($wrestler);
     *
     * // Release with specific date
     * ReleaseAction::run($wrestler, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        $wrestler->ensureCanBeReleased();

        $releaseDate = $releaseDate ?? now();

        DB::transaction(function () use ($wrestler, $releaseDate): void {
            // End current tag team partnership
            $wrestler->tagTeams()->wherePivotNull('left_at')->updateExistingPivot(
                $wrestler->tagTeams()->wherePivotNull('left_at')->pluck('tag_team_id'),
                ['left_at' => $releaseDate]
            );

            // End current stable membership
            $wrestler->stables()->wherePivotNull('left_at')->updateExistingPivot(
                $wrestler->stables()->wherePivotNull('left_at')->pluck('stable_id'),
                ['left_at' => $releaseDate]
            );

            // End current manager relationships
            $wrestler->currentManagers()->updateExistingPivot(
                $wrestler->currentManagers()->pluck('manager_id'),
                ['fired_at' => $releaseDate]
            );

            // End current championships
            $wrestler->currentChampionships()->update(['lost_at' => $releaseDate]);

            // End current suspension if active
            if ($wrestler->isSuspended()) {
                $currentSuspension = $wrestler->currentSuspension()->first();
                if ($currentSuspension) {
                    $currentSuspension->update(['ended_at' => $releaseDate]);
                }
            }

            // End current injury if active
            if ($wrestler->isInjured()) {
                $currentInjury = $wrestler->currentInjury()->first();
                if ($currentInjury) {
                    $currentInjury->update(['ended_at' => $releaseDate->toDateTimeString()]);
                }
            }

            // End current employment
            $currentEmployment = $wrestler->currentEmployment()->first();
            if ($currentEmployment) {
                $currentEmployment->update(['ended_at' => $releaseDate]);
                $wrestler->update(['status' => EmploymentStatus::Released]);
            }
        });
    }
}
