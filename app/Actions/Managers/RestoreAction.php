<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Managers\Manager;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseManagerAction
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
        DB::transaction(function () use ($manager): void {
            $this->managerRepository->restore($manager);

            // Note: No automatic relationship restoration to avoid conflicts.
            // All employment and management relationships must be re-established explicitly using separate actions.
        });
    }
}
