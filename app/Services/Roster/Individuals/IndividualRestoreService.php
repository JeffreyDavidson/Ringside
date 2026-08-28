<?php

declare(strict_types=1);

namespace App\Services\Roster\Individuals;

use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\IndividualDeletionEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class IndividualRestoreService
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly IndividualDeletionEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(Wrestler|Manager|Referee, Carbon): void|null  $afterRestore
     */
    public function restore(
        Wrestler|Manager|Referee $individual,
        Carbon $restoreDate,
        ?Closure $afterRestore = null,
    ): void {
        DB::transaction(function () use ($individual, $restoreDate, $afterRestore): void {
            $lockedIndividual = $individual->refreshForUpdate();

            $this->eligibility->ensureCanRestore($lockedIndividual);
            $this->deletionState->restore($lockedIndividual, $restoreDate);
            $afterRestore?->__invoke($lockedIndividual, $restoreDate);
        });
    }
}
