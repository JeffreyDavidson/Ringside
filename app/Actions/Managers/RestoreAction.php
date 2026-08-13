<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Lifecycle\DeletionStateManager;
use App\Models\Managers\Manager;
use App\Services\ManagerAssignmentService;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(
        private readonly ManagerAssignmentService $managerAssignments,
        private readonly DeletionStateManager $deletionState,
    ) {}

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
     */
    public function handle(Manager $manager): void
    {
        $manager->ensureCanBeRestored();

        DB::transaction(function () use ($manager): void {
            $restorationDate = now();
            $this->deletionState->restore($manager, $restorationDate);

            $manager->employments()->whereNull('ended_at')->update(['ended_at' => $restorationDate]);
            $this->managerAssignments->endCurrentAssignments($manager, $restorationDate);
        });
    }
}
