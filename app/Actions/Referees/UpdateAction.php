<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Data\Referees\RefereeData;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction
{
    use AsAction;

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
     *
     * @example
     * ```php
     * $refereeData = new RefereeData([
     *     'name' => 'Updated Name',
     *     'hometown' => 'New Hometown'
     * ]);
     * $updatedReferee = UpdateAction::run($referee, $refereeData);
     * ```
     */
    public function handle(Referee $referee, RefereeData $refereeData): Referee
    {
        if (method_exists($referee, 'ensureCanBeUpdated')) {
            $referee->ensureCanBeUpdated();
        }

        return DB::transaction(function () use ($referee, $refereeData): Referee {
            // Update the referee's basic information
            $referee->update([
                'first_name' => $refereeData->first_name,
                'last_name' => $refereeData->last_name,
            ]);

            // Handle employment using EmployAction for consistency
            if (! is_null($refereeData->employment_date) && ! $referee->isEmployed()) {
                $employmentDate = DateHelper::resolveDate($refereeData->employment_date);
                $this->employAction->handle($referee, $employmentDate);
            }

            return $referee;
        });
    }
}
