<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeReleasedException;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    /**
     * Release a referee from employment.
     *
     * This handles the complete referee release workflow:
     * - Validates the referee can be released (currently employed)
     * - Ends suspension and injury if active
     * - Ends employment period with the specified date
     * - Maintains all historical records for tracking purposes
     *
     * @param  Referee  $referee  The referee to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When referee cannot be released due to business rules
     */
    public function handle(Referee $referee, ?Carbon $releaseDate = null): void
    {
        $referee->ensureCanBeReleased();

        $releaseDate = DateHelper::resolveDate($releaseDate);

        DB::transaction(function () use ($referee, $releaseDate): void {
            // End suspension or injury if active (referee cannot be both suspended and injured)
            if ($referee->isSuspended()) {
                $currentSuspension = $referee->currentSuspension()->first();
                if ($currentSuspension) {
                    $currentSuspension->update(['ended_at' => $releaseDate]);
                }
            } elseif ($referee->isInjured()) {
                $currentInjury = $referee->currentInjury()->first();
                if ($currentInjury) {
                    $currentInjury->update(['ended_at' => $releaseDate->toDateTimeString()]);
                }
            }

            if ($referee->currentEmployment()->exists()) {
                $referee->employments()->whereNull('ended_at')->update(['ended_at' => $releaseDate]);
                $referee->update(['status' => EmploymentStatus::Released]);
            }
        });
    }
}
