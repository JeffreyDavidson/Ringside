<?php

declare(strict_types=1);

namespace App\Services\Roster\Individuals;

use App\Lifecycle\DeletionPeriodCloser;
use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\IndividualDeletionEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class IndividualDeletionService
{
    public function __construct(
        private readonly DeletionPeriodCloser $periods,
        private readonly DeletionStateManager $deletionState,
        private readonly IndividualDeletionEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(Wrestler|Manager|Referee, Carbon): void|null  $afterPeriodsClosed
     */
    public function delete(
        Wrestler|Manager|Referee $individual,
        Carbon $deletionDate,
        ?Closure $afterPeriodsClosed = null,
    ): void {
        DB::transaction(function () use ($individual, $deletionDate, $afterPeriodsClosed): void {
            $lockedIndividual = $individual::query()
                ->withTrashed()
                ->whereKey($individual->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanDelete($lockedIndividual);
            $this->periods->close($lockedIndividual, $deletionDate);
            $afterPeriodsClosed?->__invoke($lockedIndividual, $deletionDate);
            $this->deletionState->delete($lockedIndividual, $deletionDate);
        });
    }
}
