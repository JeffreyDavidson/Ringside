<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Data\Referees\RefereeData;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(
        private EmployAction $employAction
    ) {}

    /**
     * Update a referee.
     *
     * This handles the complete referee update workflow:
     * - Updates referee personal and professional information
     * - Uses EmployAction for consistent employment handling if employment_date is provided
     * - Maintains data integrity throughout the update process
     *
     * ARCHITECTURAL PATTERN:
     * Uses EmployAction for employment handling, following the same pattern as other
     * referee actions for consistency.
     *
     * @param  Referee  $referee  The referee to update
     * @param  RefereeData  $refereeData  The updated referee information
     * @return Referee The updated referee instance
     */
    public function handle(Referee $referee, RefereeData $refereeData): Referee
    {
        return DB::transaction(function () use ($referee, $refereeData): Referee {
            $lockedReferee = $referee->refreshForUpdate();

            $lockedReferee->update([
                'first_name' => $refereeData->first_name,
                'last_name' => $refereeData->last_name,
            ]);

            if ($refereeData->employment_date !== null && ! $lockedReferee->currentEmployment()->exists()) {
                $this->employAction->handle($lockedReferee, $refereeData->employment_date);
            }

            return $lockedReferee;
        });
    }
}
