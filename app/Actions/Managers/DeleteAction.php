<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\Cascades\ManagerDeletionCascadeStrategy;
use App\Actions\Concerns\StatusTransitionPipeline;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction
{
    use AsAction;

    /**
     * Delete a manager.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * MANAGEMENT IMPACT:
     * - Ends all current wrestler and tag team management relationships
     * - Preserves management history for reporting
     * - No impact on past management records or statistics
     *
     * EMPLOYMENT IMPACT:
     * - Uses StatusTransitionPipeline.delete() to end all active statuses
     * - Automatically handles employment, retirement, suspension, and injury ending
     * - Preserves manager employment history for administrative records
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status handling, following the same
     * pattern as other manager actions.
     *
     * OTHER CLEANUP:
     * - Soft deletes the manager record
     * - Allows for future restoration if needed
     * - Maintains referential integrity with historical data
     *
     * @param  Manager  $manager  The manager to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     *
     * @example
     * ```php
     * // Delete manager immediately
     * $manager = Manager::find(1);
     * DeleteAction::run($manager);
     *
     * // Delete with specific date
     * DeleteAction::run($manager, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $deletionDate = null): void
    {
        $deletionDate = DateHelper::resolveDate($deletionDate);

        DB::transaction(function () use ($manager, $deletionDate): void {
            // Handle manager status cleanup using StatusTransitionPipeline with cascade strategy
            StatusTransitionPipeline::delete($manager, $deletionDate)
                ->withCascade(ManagerDeletionCascadeStrategy::comprehensive())
                ->execute();

            // Soft delete the manager record
            $manager->delete();
        });
    }
}
