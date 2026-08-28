<?php

declare(strict_types=1);

namespace App\Services\Roster\Stables;

use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Roster\Stables\StableDeletionEligibility;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class StableDeletionService
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly StableDeletionEligibility $eligibility,
    ) {}

    public function delete(Stable $stable, Carbon $deletionDate): void
    {
        DB::transaction(function () use ($stable, $deletionDate): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureCanDelete($lockedStable);
            $this->deletionState->delete($lockedStable, $deletionDate);
        });
    }

    public function restore(Stable $stable, Carbon $restoreDate): void
    {
        DB::transaction(function () use ($stable, $restoreDate): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureCanRestore($lockedStable);
            $this->deletionState->restore($lockedStable, $restoreDate);
        });
    }
}
