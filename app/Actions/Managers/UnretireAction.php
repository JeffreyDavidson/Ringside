<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Individuals\IndividualUnretirementService;
use Illuminate\Support\Carbon;

class UnretireAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly IndividualUnretirementService $unretirement,
    ) {}

    /**
     * Unretire a retired manager and return them to active talent management.
     *
     * This handles the complete manager unretirement workflow:
     * - Validates the manager can be unretired (currently retired)
     * - Ends the current retirement period through RetirementPeriodManager
     * - Optionally starts a new employment period from the unretirement date
     * - Preserves all historical retirement and employment records
     *
     * @param  Manager  $manager  The manager to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @param  bool  $employImmediately  Whether to employ the manager immediately
     * @throws CannotBeUnretiredException When manager cannot be unretired due to business rules
     */
    public function handle(Manager $manager, ?Carbon $unretiredDate = null, bool $employImmediately = true): void
    {
        $unretiredDate = $unretiredDate ?? now();

        $this->unretirement->unretire($manager, $unretiredDate, function (Wrestler|Manager|Referee $lockedManager, Carbon $date) use ($employImmediately): void {
            if ($employImmediately) {
                if ($lockedManager instanceof Manager) {
                    $this->employmentPeriods->start($lockedManager, $date, LifecycleTransitionType::Employed);
                }
            }
        });
    }
}
