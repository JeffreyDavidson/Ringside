<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Status\CannotBeReleasedException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseWrestlerAction
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
     *
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
        // Validate business rules before proceeding
        $wrestler->ensureCanBeReleased();

        $releaseDate = $this->getEffectiveDate($releaseDate);

        DB::transaction(function () use ($wrestler, $releaseDate): void {
            // End current tag team partnerships
            $this->wrestlerRepository->removeFromCurrentTagTeam($wrestler, $releaseDate);

            // End current stable membership
            $this->wrestlerRepository->removeFromCurrentStable($wrestler, $releaseDate);

            // End current manager relationships
            $this->removeCurrentManagers($wrestler, $releaseDate);

            // End current suspension if active
            if ($wrestler->isSuspended()) {
                $this->wrestlerRepository->endSuspension($wrestler, $releaseDate);
            }

            // End current injury if active
            if ($wrestler->isInjured()) {
                $this->wrestlerRepository->endInjury($wrestler, $releaseDate);
            }

            // End current employment
            $this->wrestlerRepository->endEmployment($wrestler, $releaseDate);
        });
    }
}
