<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\WrestlerDeletionCascadeStrategy;
use App\Lifecycle\DeletionPeriodCloser;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(private readonly DeletionPeriodCloser $periods) {}

    /**
     * Delete a wrestler.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * EMPLOYMENT IMPACT:
     * - Ends active employment, retirement, suspension, and injury periods
     * - Preserves wrestler employment history for administrative records
     *
     * RELATIONSHIP IMPACT:
     * - Uses WrestlerDeletionCascadeStrategy to end all professional relationships
     * - Removes wrestler from current tag teams (teams may need new members)
     * - Ends stable memberships (stables continue with remaining members)
     * - Terminates management contracts (managers may manage other talent)
     * - Vacates any held championships (titles become available)
     *
     * OTHER CLEANUP:
     * - Soft deletes the wrestler record
     * - Maintains referential integrity with historical data
     */
    public function handle(Wrestler $wrestler, ?Carbon $deletionDate = null): void
    {
        if ($wrestler->trashed()) {
            throw new Exception("Wrestler '{$wrestler->name}' is already deleted.");
        }

        $deletionDate = DateHelper::resolveDate($deletionDate);

        DB::transaction(function () use ($wrestler, $deletionDate): void {
            $this->periods->close($wrestler, $deletionDate);
            WrestlerDeletionCascadeStrategy::endAllRelationships()($wrestler, $deletionDate, 'delete');

            // Soft delete the wrestler record
            $wrestler->delete();
        });
    }
}
