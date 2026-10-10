<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\Roster\Stables\CannotBeRestoredException;
use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Roster\Stables\StableDeletionEligibility;
use App\Lifecycle\Roster\Stables\StableNameLock;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly StableDeletionEligibility $eligibility,
        private readonly StableNameLock $nameLock,
    ) {}

    /**
     * Restore a soft-deleted stable.
     *
     * This handles the stable restoration workflow:
     * - Validates the stable is deleted and has no active name conflict; a stable without a promotion has no
     *   database-level name guard on MySQL, so it first takes the name lock, before its own row lock
     * - Restores the soft-deleted stable record from trash
     * - Preserves all historical member relationships and match history
     * - Leaves reunion and activation as explicit subsequent operations
     */
    public function handle(Stable $stable, ?Carbon $restoreDate = null): void
    {
        $effectiveDate = $restoreDate ?? now();

        DB::transaction(function () use ($stable, $effectiveDate): void {
            if ($stable->promotion_id === null) {
                $this->nameLock->lock($stable->name);
            }

            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureCanRestore($lockedStable);

            try {
                $this->deletionState->restore($lockedStable, $effectiveDate);
            } catch (UniqueConstraintViolationException) {
                // A concurrent request took the name of this promotion after the check above; the unique index refused the restore.
                throw CannotBeRestoredException::nameConflict($lockedStable, $lockedStable->name);
            }
        });
    }
}
