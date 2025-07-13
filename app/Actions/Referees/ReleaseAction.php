<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Status\CannotBeReleasedException;
use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseRefereeAction
{
    use AsAction;

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
     *
     * @throws CannotBeReleasedException When referee cannot be released due to business rules
     *
     * @example
     * ```php
     * // Release referee immediately
     * ReleaseAction::run($referee);
     *
     * // Release with specific date
     * ReleaseAction::run($referee, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Referee $referee, ?Carbon $releaseDate = null): void
    {
        $referee->ensureCanBeReleased();

        $releaseDate = $this->getEffectiveDate($releaseDate);

        DB::transaction(function () use ($referee, $releaseDate): void {
            // End suspension or injury if active (referee cannot be both suspended and injured)
            if ($referee->isSuspended()) {
                $this->refereeRepository->endSuspension($referee, $releaseDate);
            } elseif ($referee->isInjured()) {
                $this->refereeRepository->endInjury($referee, $releaseDate);
            }

            $this->refereeRepository->endEmployment($referee, $releaseDate);
        });
    }
}
