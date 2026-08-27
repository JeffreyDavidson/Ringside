<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Models\Roster\Referees\Referee;
use App\Services\IndividualInjuryService;
use Illuminate\Support\Carbon;

class InjureAction
{
    public function __construct(
        private readonly IndividualInjuryService $injury,
    ) {}

    /**
     * Record a referee injury.
     *
     * This handles the complete referee injury workflow:
     * - Validates the referee can be injured (currently employed, not already injured)
     * - Creates the injury period through the shared lifecycle component
     * - Removes the referee from active match officiating duties
     * - Maintains employment status while marking as unavailable due to injury
     *
     * @param  Referee  $referee  The referee to mark as injured
     * @param  Carbon|null  $injureDate  The injury date (defaults to now)
     * @throws CannotBeInjuredException When referee cannot be injured due to business rules
     */
    public function handle(Referee $referee, ?Carbon $injureDate = null): void
    {
        $injureDate = $injureDate ?? now();

        $this->injury->injure($referee, $injureDate);
    }
}
