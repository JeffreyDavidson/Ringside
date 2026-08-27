<?php

declare(strict_types=1);

namespace App\Services;

use App\Lifecycle\IndividualEmploymentEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class IndividualReleaseService
{
    public function __construct(
        private readonly IndividualReleasePeriodService $releasePeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(Wrestler|Manager|Referee, Carbon): void|null  $afterRelease
     */
    public function release(
        Wrestler|Manager|Referee $individual,
        Carbon $releaseDate,
        ?Closure $afterRelease = null,
    ): void {
        DB::transaction(function () use ($individual, $releaseDate, $afterRelease): void {
            $lockedIndividual = $individual::query()
                ->whereKey($individual->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanRelease($lockedIndividual);
            $this->releasePeriods->end($lockedIndividual, $releaseDate);
            $afterRelease?->__invoke($lockedIndividual, $releaseDate);
        });
    }
}
