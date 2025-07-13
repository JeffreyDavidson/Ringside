<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Status\CannotBeClearedFromInjuryException;
use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class HealAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Heal a referee from injury and return them to active officiating.
     *
     * This handles the complete injury recovery workflow:
     * - Validates the referee can be healed from injury (currently injured)
     * - Ends the current injury period with the specified recovery date
     * - Restores the referee to active officiating status
     * - Makes the referee available for match assignments again
     * - Preserves injury history for medical and administrative records
     *
     * @param  Referee  $referee  The injured referee to heal
     * @param  Carbon|null  $recoveryDate  The recovery date (defaults to now)
     *
     * @throws CannotBeClearedFromInjuryException When referee cannot be healed due to business rules
     *
     * @example
     * ```php
     * // Heal injury immediately
     * HealAction::run($referee);
     *
     * // Heal injury with specific recovery date
     * HealAction::run($referee, Carbon::parse('2024-02-01'));
     * ```
     */
    public function handle(Referee $referee, ?Carbon $recoveryDate = null): void
    {
        $referee->ensureCanBeHealed();

        $recoveryDate = $this->getEffectiveDate($recoveryDate);

        DB::transaction(function () use ($referee, $recoveryDate): void {
            $this->refereeRepository->endInjury($referee, $recoveryDate);
        });
    }
}
