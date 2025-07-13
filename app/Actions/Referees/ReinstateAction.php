<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Status\CannotBeReinstatedException;
use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Reinstate a suspended referee.
     *
     * This handles the complete referee reinstatement workflow:
     * - Validates the referee can be reinstated (currently suspended)
     * - Ends the current suspension period with the specified date
     * - Restores the referee to active officiating status
     * - Makes the referee available for match assignments
     *
     * @param  Referee  $referee  The referee to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     *
     * @throws CannotBeReinstatedException When referee cannot be reinstated due to business rules
     *
     * @example
     * ```php
     * // Reinstate referee immediately
     * ReinstateAction::run($referee);
     *
     * // Reinstate with specific date
     * ReinstateAction::run($referee, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Referee $referee, ?Carbon $reinstatementDate = null): void
    {
        $referee->ensureCanBeReinstated();

        $reinstatementDate = $this->getEffectiveDate($reinstatementDate);

        DB::transaction(function () use ($referee, $reinstatementDate): void {
            $this->refereeRepository->endSuspension($referee, $reinstatementDate);
        });
    }
}
