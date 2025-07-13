<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseWrestlerAction
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
     *
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
        // Validate business rules before proceeding
        $this->validateCanBeReinstated($wrestler);

        $reinstatementDate = $this->getEffectiveDate($reinstatementDate);

        // End current suspension if active
        if ($wrestler->isSuspended()) {
            $this->wrestlerRepository->endSuspension($wrestler, $reinstatementDate);
        }

        // End current injury if active
        if ($wrestler->isInjured()) {
            $this->wrestlerRepository->endInjury($wrestler, $reinstatementDate);
        }
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
