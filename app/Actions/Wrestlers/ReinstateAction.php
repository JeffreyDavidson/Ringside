<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;

class ReinstateAction
{
    public function __construct(private readonly SuspensionPeriodManager $suspensionPeriods) {}

    /**
     * Reinstate a wrestler and make them available for employment.
     *
     * This handles the complete wrestler reinstatement workflow:
     * - Validates the wrestler can be reinstated
     * - Ends the suspension period through the shared lifecycle component
     * - Makes the wrestler available for new employment opportunities
     *
     * @param  Wrestler  $wrestler  The wrestler to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @throws CannotBeReinstatedException When wrestler cannot be reinstated due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $reinstatementDate = null): void
    {
        $wrestler->ensureCanBeReinstated();

        $reinstatementDate = DateHelper::resolveDate($reinstatementDate);

        $this->suspensionPeriods->end($wrestler, $reinstatementDate);
    }
}
