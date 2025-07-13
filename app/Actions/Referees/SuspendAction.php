<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Status\CannotBeSuspendedException;
use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Suspend a referee.
     *
     * This handles the complete referee suspension workflow:
     * - Validates the referee can be suspended (currently employed, not already suspended)
     * - Creates a suspension record with the specified start date
     * - Removes the referee from active match officiating duties
     * - Maintains employment status while restricting availability
     *
     * @param  Referee  $referee  The referee to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     *
     * @throws CannotBeSuspendedException When referee cannot be suspended due to business rules
     *
     * @example
     * ```php
     * // Suspend referee immediately
     * SuspendAction::run($referee);
     *
     * // Schedule suspension for future date
     * SuspendAction::run($referee, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Referee $referee, ?Carbon $suspensionDate = null): void
    {
        $referee->ensureCanBeSuspended();

        $suspensionDate = $this->getEffectiveDate($suspensionDate);

        DB::transaction(function () use ($referee, $suspensionDate): void {
            $this->refereeRepository->createSuspension($referee, $suspensionDate);
        });
    }
}
