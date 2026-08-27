<?php

declare(strict_types=1);

namespace App\Services\Venues;

use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\VenueDeletionEligibility;
use App\Models\Events\Venue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class VenueDeletionService
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly VenueDeletionEligibility $eligibility,
    ) {}

    public function delete(Venue $venue, Carbon $deletionDate): void
    {
        DB::transaction(function () use ($venue, $deletionDate): void {
            $lockedVenue = Venue::query()
                ->withTrashed()
                ->whereKey($venue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->deletionState->delete($lockedVenue, $deletionDate);
        });
    }

    public function restore(Venue $venue, Carbon $restoreDate): void
    {
        DB::transaction(function () use ($venue, $restoreDate): void {
            $lockedVenue = Venue::query()
                ->withTrashed()
                ->whereKey($venue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanRestore($lockedVenue);
            $this->deletionState->restore($lockedVenue, $restoreDate);
        });
    }
}
