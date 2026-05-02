<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Actions\Concerns\WrestlerDeletionCascadeStrategy;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction
{
    use AsAction;

    /**
     * Delete a wrestler.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * EMPLOYMENT IMPACT:
     * - Uses StatusTransitionPipeline.delete() to end all active statuses
     * - Automatically handles employment, retirement, suspension, and injury ending
     * - Preserves wrestler employment history for administrative records
     *
     * RELATIONSHIP IMPACT:
     * - Uses WrestlerDeletionCascadeStrategy to end all professional relationships
     * - Removes wrestler from current tag teams (teams may need new members)
     * - Ends stable memberships (stables continue with remaining members)
     * - Terminates management contracts (managers may manage other talent)
     * - Vacates any held championships (titles become available)
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status handling and cascade
     * strategies for relationship cleanup, following the same pattern as other
     * wrestler actions.
     *
     * OTHER CLEANUP:
     * - Soft deletes the wrestler record
     * - Maintains referential integrity with historical data
     */
    public function handle(Wrestler $wrestler, ?Carbon $deletionDate = null): void
    {
        if (method_exists($wrestler, 'ensureCanBeDeleted')) {
            $wrestler->ensureCanBeDeleted();
        }

        $deletionDate = DateHelper::resolveDate($deletionDate);

        DB::transaction(function () use ($wrestler, $deletionDate): void {
            // Handle wrestler status and relationship cleanup using StatusTransitionPipeline
            StatusTransitionPipeline::delete($wrestler, $deletionDate)
                ->withCascade(WrestlerDeletionCascadeStrategy::endAllRelationships())
                ->execute();

            // Soft delete the wrestler record
            $wrestler->delete();
        });
    }
}
