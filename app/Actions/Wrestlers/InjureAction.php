<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Individuals\IndividualInjuryService;
use Illuminate\Support\Carbon;

class InjureAction
{
    public function __construct(
        private readonly IndividualInjuryService $injury,
    ) {}

    /**
     * Injure a wrestler and make them unavailable for competition.
     *
     * This handles the complete wrestler injury workflow:
     * - Validates the wrestler can be injured
     * - Creates the injury period through the shared lifecycle component
     * - Makes the wrestler unavailable for match bookings
     * - May affect tag team bookability if wrestler is in a team
     *
     * @param  Wrestler  $wrestler  The wrestler to injure
     * @param  Carbon|null  $injuryDate  The injury start date (defaults to now)
     * @throws CannotBeInjuredException When wrestler cannot be injured due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $injuryDate = null): void
    {
        $injuryDate = $injuryDate ?? now();

        $this->injury->injure($wrestler, $injuryDate);
    }
}
