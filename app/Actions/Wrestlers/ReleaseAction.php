<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Lifecycle\IndividualEmploymentEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\IndividualReleasePeriodService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly IndividualReleasePeriodService $releasePeriods,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
        private readonly IndividualEmploymentEligibility $eligibility,
    ) {}

    /**
     * Release a wrestler from employment and end all current relationships.
     *
     * This handles the complete wrestler release workflow:
     * - Validates the wrestler can be released
     * - Ends employment, suspension, and injury through lifecycle period managers
     * - Ends all current professional relationships through a typed domain action
     * - Maintains all historical records for tracking purposes
     * - Preserves the operation's transaction boundary
     *
     * @param  Wrestler  $wrestler  The wrestler to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When wrestler cannot be released due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        $releaseDate = $releaseDate ?? now();

        DB::transaction(function () use ($wrestler, $releaseDate): void {
            $lockedWrestler = Wrestler::query()->whereKey($wrestler->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanRelease($lockedWrestler);

            $this->releasePeriods->end($lockedWrestler, $releaseDate);

            $this->endCurrentRelationships->handle($lockedWrestler, $releaseDate);
        });
    }
}
