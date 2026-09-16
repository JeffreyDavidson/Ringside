<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\InjuryPeriodManager;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

    /**
     * Release a wrestler from employment and end all current relationships.
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        $effectiveDate = $releaseDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanRelease($lockedWrestler);

            $this->employmentPeriods->end($lockedWrestler, $effectiveDate, LifecycleTransitionType::Released);

            if ($lockedWrestler->currentSuspension()->exists()) {
                $this->suspensionPeriods->end($lockedWrestler, $effectiveDate);
            } elseif ($lockedWrestler->currentInjury()->exists()) {
                $this->injuryPeriods->end($lockedWrestler, $effectiveDate);
            }

            $this->endCurrentRelationships->handle($lockedWrestler, $effectiveDate);
        });
    }
}
