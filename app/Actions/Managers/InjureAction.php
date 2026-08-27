<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Models\Roster\Managers\Manager;
use App\Services\Roster\Individuals\IndividualInjuryService;
use Illuminate\Support\Carbon;

class InjureAction
{
    public function __construct(
        private readonly IndividualInjuryService $injury,
    ) {}

    /**
     * Record a manager injury.
     *
     * This handles the complete manager injury workflow:
     * - Validates the manager can be injured (currently employed, not already injured)
     * - Creates the injury period through the shared lifecycle component
     * - Temporarily removes the manager from active wrestler/tag team management duties
     * - Maintains employment status while marking as unavailable due to injury
     *
     * @param  Manager  $manager  The manager to mark as injured
     * @param  Carbon|null  $injureDate  The injury date (defaults to now)
     * @throws CannotBeInjuredException When manager cannot be injured due to business rules
     */
    public function handle(Manager $manager, ?Carbon $injureDate = null): void
    {
        $injureDate = $injureDate ?? now();

        $this->injury->injure($manager, $injureDate);
    }
}
