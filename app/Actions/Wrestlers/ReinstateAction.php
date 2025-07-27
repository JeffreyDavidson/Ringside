<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction
{
    use AsAction;

    /**
     * Reinstate a wrestler and make them available for employment.
     *
     * This handles the complete wrestler reinstatement workflow:
     * - Validates the wrestler can be reinstated (not currently employed)
     * - Ends any current suspension or injury if active
     * - Makes the wrestler available for new employment opportunities
     *
     * @param  Wrestler  $wrestler  The wrestler to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @throws CannotBeReinstatedException When wrestler cannot be reinstated due to business rules
     *
     * @example
     * ```php
     * // Reinstate wrestler immediately
     * ReinstateAction::run($wrestler);
     *
     * // Reinstate with specific date
     * ReinstateAction::run($wrestler, Carbon::parse('2024-01-15'));
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $reinstatementDate = null): void
    {
        $this->validateCanBeReinstated($wrestler);

        $reinstatementDate = $reinstatementDate ?? now();

        DB::transaction(function () use ($wrestler, $reinstatementDate): void {
            // End current suspension if active
            if ($wrestler->isSuspended()) {
                $currentSuspension = $wrestler->currentSuspension()->first();
                if ($currentSuspension) {
                    $currentSuspension->update(['ended_at' => $reinstatementDate]);
                }
            }

            // End current injury if active
            if ($wrestler->isInjured()) {
                $currentInjury = $wrestler->currentInjury()->first();
                if ($currentInjury) {
                    $currentInjury->update(['ended_at' => $reinstatementDate->toDateTimeString()]);
                }
            }
        });
    }

    /**
     * Validate that a wrestler can be reinstated.
     *
     * This method allows both suspended and injured wrestlers to be reinstated,
     * but prevents reinstatement of unemployed, released, or retired wrestlers.
     *
     * @throws CannotBeReinstatedException
     */
    private function validateCanBeReinstated(Wrestler $wrestler): void
    {
        if ($wrestler->hasStatus(EmploymentStatus::Unemployed)) {
            throw new CannotBeReinstatedException();
        }

        if ($wrestler->isReleased()) {
            throw new CannotBeReinstatedException();
        }

        if ($wrestler->hasFutureEmployment()) {
            throw new CannotBeReinstatedException();
        }

        if ($wrestler->isRetired()) {
            throw new CannotBeReinstatedException();
        }

        if ($wrestler->isBookable()) {
            throw new CannotBeReinstatedException();
        }
    }
}
