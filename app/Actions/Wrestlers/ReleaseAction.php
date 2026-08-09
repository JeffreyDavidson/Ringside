<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\WrestlerRetirementCascadeStrategy;
use App\Exceptions\Roster\CannotBeReleasedException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
    ) {}

    /**
     * Release a wrestler from employment and end all current relationships.
     *
     * This handles the complete wrestler release workflow:
     * - Validates the wrestler can be released
     * - Ends employment, suspension, and injury through lifecycle period managers
     * - Cascades to end all professional relationships (same as retirement pattern)
     * - Maintains all historical records for tracking purposes
     * - Preserves the operation's transaction boundary
     *
     * @param  Wrestler  $wrestler  The wrestler to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When wrestler cannot be released due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        $wrestler->ensureCanBeReleased();

        $releaseDate = DateHelper::resolveDate($releaseDate);

        DB::transaction(function () use ($wrestler, $releaseDate): void {
            $this->employmentPeriods->end($wrestler, $releaseDate);

            if ($wrestler->isSuspended()) {
                $this->suspensionPeriods->end($wrestler, $releaseDate);
            } elseif ($wrestler->isInjured()) {
                $this->injuryPeriods->end($wrestler, $releaseDate);
            }

            WrestlerRetirementCascadeStrategy::endAllRelationships()($wrestler, $releaseDate, 'release');
        });
    }
}
