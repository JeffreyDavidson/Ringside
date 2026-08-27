<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Individuals\IndividualRestoreService;
use App\Services\Roster\Relationships\ManagerAssignmentService;
use Illuminate\Support\Carbon;

class RestoreAction
{
    public function __construct(
        private readonly ManagerAssignmentService $managerAssignments,
        private readonly IndividualRestoreService $restore,
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
        $this->restore->restore($manager, now(), function (Wrestler|Manager|Referee $lockedIndividual, Carbon $date): void {
            if (! $lockedIndividual instanceof Manager) {
                return;
            }

            $lockedIndividual->employments()->whereNull('ended_at')->update(['ended_at' => $date]);
            $this->managerAssignments->endCurrentAssignments($lockedIndividual, $date);
        });
    }
}
