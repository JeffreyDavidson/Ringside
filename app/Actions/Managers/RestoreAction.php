<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Managers\Manager;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction
{
    use AsAction;

    /**
     * Restore a soft-deleted manager.
     *
     * This handles the complete manager restoration workflow:
     * - Restores the soft-deleted manager record
     * - Makes the manager available for future employment and talent management
     * - Preserves all historical employment, injury, suspension, and management records
     * - Does not automatically restore employment relationships
     * - Requires separate employment action to make manager active again
     *
     * @param  Manager  $manager  The soft-deleted manager to restore
     *
     * @example
     * ```php
     * $deletedManager = Manager::onlyTrashed()->find(1);
     * RestoreAction::run($deletedManager);
     * ```
     */
    public function handle(Manager $manager): void
    {
        $manager->ensureCanBeRestored();

        DB::transaction(function () use ($manager): void {
            $manager->restore();

            $restorationDate = now();

            $manager->employments()->whereNull('ended_at')->update(['ended_at' => $restorationDate]);
            $manager->removeFromCurrentWrestlers($restorationDate);
            $manager->removeFromCurrentTagTeams($restorationDate);
        });
    }
}
