<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\StableDeletionEligibility;
use App\Models\Stables\Stable;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly StableDeletionEligibility $eligibility,
    ) {}

    /**
     * Restore a soft-deleted stable.
     *
     * This handles the stable restoration workflow:
     * - Validates the stable is deleted and has no active name conflict
     * - Restores the soft-deleted stable record from trash
     * - Preserves all historical member relationships and match history
     * - Leaves reunion and activation as explicit subsequent operations
     */
    public function handle(Stable $stable): void
    {
        DB::transaction(function () use ($stable): void {
            $lockedStable = Stable::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($stable->getKey());

            $this->eligibility->ensureCanRestore($lockedStable);
            $this->deletionState->restore($lockedStable, now());
        });
    }
}
