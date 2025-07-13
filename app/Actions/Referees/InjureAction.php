<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Status\CannotBeInjuredException;
use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Record a referee injury.
     *
     * This handles the complete referee injury workflow:
     * - Validates the referee can be injured (currently employed, not already injured)
     * - Creates an injury record with the specified start date
     * - Removes the referee from active match officiating duties
     * - Maintains employment status while marking as unavailable due to injury
     *
     * @param  Referee  $referee  The referee to mark as injured
     * @param  Carbon|null  $injureDate  The injury date (defaults to now)
     *
     * @throws CannotBeInjuredException When referee cannot be injured due to business rules
     *
     * @example
     * ```php
     * // Mark referee as injured immediately
     * InjureAction::run($referee);
     *
     * // Record injury with specific date
     * InjureAction::run($referee, Carbon::parse('2024-01-15'));
     * ```
     */
    public function handle(Referee $referee, ?Carbon $injureDate = null): void
    {
        $referee->ensureCanBeInjured();

        $injureDate = $this->getEffectiveDate($injureDate);

        DB::transaction(function () use ($referee, $injureDate): void {
            $this->refereeRepository->createInjury($referee, $injureDate);
        });
    }
}
