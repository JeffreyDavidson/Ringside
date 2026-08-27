<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Models\Roster\Referees\Referee;
use App\Services\Roster\Individuals\IndividualEmploymentService;
use Illuminate\Support\Carbon;

class EmployAction
{
    public function __construct(
        private readonly IndividualEmploymentService $employment,
    ) {}

    /**
     * Employ a referee.
     *
     * This handles the complete referee employment workflow:
     * - Validates the referee can be employed (not retired, not already employed)
     * - Creates the employment record through the shared lifecycle component
     * - Makes the referee available for match officiating assignments
     *
     * @param  Referee  $referee  The referee to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws CannotBeEmployedException When the referee cannot be employed
     */
    public function handle(Referee $referee, ?Carbon $employmentDate = null): void
    {
        $this->employment->employ($referee, $employmentDate ?? now());
    }
}
