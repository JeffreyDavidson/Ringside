<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\IndividualEmploymentService;
use Illuminate\Support\Carbon;

class EmployAction
{
    public function __construct(
        private readonly IndividualEmploymentService $employment,
        private readonly EmployCurrentManagersAction $employCurrentManagers,
    ) {}

    /**
     * Employ a wrestler and activate their career.
     *
     * This handles the complete wrestler employment workflow:
     * - Validates the wrestler can be employed (not retired, not already employed)
     * - Prepares the wrestler by ending any active suspension or injury status
     * - Creates the employment record through the shared lifecycle component
     * - Employs any current managers who are not yet employed through cascading
     * - Makes the wrestler available for match bookings and storylines
     *
     * @param  Wrestler  $wrestler  The wrestler to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws CannotBeEmployedException When the wrestler cannot be employed
     */
    public function handle(Wrestler $wrestler, ?Carbon $employmentDate = null): void
    {
        $this->employment->employ(
            $wrestler,
            $employmentDate ?? now(),
            function (Wrestler|Manager|Referee $lockedIndividual, Carbon $date): void {
                if (! $lockedIndividual instanceof Wrestler) {
                    return;
                }

                $this->employCurrentManagers->handle($lockedIndividual, $date);
            },
        );
    }
}
