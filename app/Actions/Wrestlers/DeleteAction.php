<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\IndividualDeletionService;
use Illuminate\Support\Carbon;

class DeleteAction
{
    public function __construct(
        private readonly IndividualDeletionService $deletion,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

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
     * - Ends all current professional relationships through a typed domain action
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
        $this->deletion->delete(
            $wrestler,
            $deletionDate ?? now(),
            function (Wrestler|Manager|Referee $lockedIndividual, Carbon $date): void {
                if (! $lockedIndividual instanceof Wrestler) {
                    return;
                }

                $this->endCurrentRelationships->handle($lockedIndividual, $date);
            },
        );
    }
}
