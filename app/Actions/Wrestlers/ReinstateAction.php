<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Individuals\IndividualSuspensionService;
use Illuminate\Support\Carbon;

class ReinstateAction
{
    public function __construct(
        private readonly IndividualSuspensionService $suspension,
    ) {}

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
        $this->suspension->reinstate($wrestler, $reinstatementDate ?? now());
    }
}
