<?php

declare(strict_types=1);

namespace App\Services;

use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\StableDeletionEligibility;
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
            $lockedStable = Stable::query()
                ->withTrashed()
                ->whereKey($stable->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanDelete($lockedStable);
            $this->deletionState->deleteWithinTransaction($lockedStable, $deletionDate);
        });
    }

    public function restore(Stable $stable, Carbon $restoreDate): void
    {
        DB::transaction(function () use ($stable, $restoreDate): void {
            $lockedStable = Stable::query()
                ->withTrashed()
                ->whereKey($stable->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanRestore($lockedStable);
            $this->deletionState->restoreWithinTransaction($lockedStable, $restoreDate);
        });
    }
}
