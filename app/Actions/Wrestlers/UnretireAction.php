<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\IndividualUnretirementService;
use Illuminate\Support\Carbon;

class UnretireAction
{
    public function __construct(
        private readonly IndividualUnretirementService $unretirement,
        private readonly EmployAction $employ,
    ) {}

    /**
     * Unretire a wrestler and return them to active competition.
     *
     * This handles the complete wrestler comeback workflow with flexible employment options:
     * - Validates the wrestler can come out of retirement (business rule compliance)
     * - Ends the current retirement period through RetirementPeriodManager
     * - Optionally employs the wrestler immediately or leaves unemployed for manual employment
     * - Restores the wrestler to available status for match bookings
     * - Makes the wrestler available for new career opportunities
     * - Preserves all historical retirement records
     *
     * @param  Wrestler  $wrestler  The wrestler to unretire
     * @param  Carbon|null  $unretirementDate  The unretirement date (defaults to now)
     * @param  bool  $employImmediately  Whether to employ the wrestler immediately (default: true)
     * @throws CannotBeUnretiredException When wrestler cannot be unretired due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $unretirementDate = null, bool $employImmediately = true): void
    {
        $unretirementDate = $unretirementDate ?? now();

        $this->unretirement->unretire($wrestler, $unretirementDate, function (Wrestler|Manager|Referee $lockedWrestler, Carbon $date) use ($employImmediately): void {
            if ($employImmediately) {
                if ($lockedWrestler instanceof Wrestler) {
                    $this->employ->handle($lockedWrestler, $date);
                }
            }
        });
    }
}
