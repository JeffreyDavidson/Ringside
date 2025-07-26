<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Data\Referees\RefereeData;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Referees\Referee;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction
{
    use AsAction;

    /**
     * Update a referee.
     *
     * This handles the complete referee update workflow:
     * - Updates referee personal and professional information
     * - Handles conditional employment if employment_date is modified
     * - Maintains data integrity throughout the update process
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
        return DB::transaction(function () use ($referee, $refereeData): Referee {
            // Update the referee's basic information
            $referee->update([
                'first_name' => $refereeData->first_name,
                'last_name' => $refereeData->last_name,
            ]);

            // Create employment record if employment_date is provided and referee is eligible
            if (! is_null($refereeData->employment_date) && ! $referee->isEmployed()) {
                // Create employment record
                $referee->employments()->updateOrCreate(
                    ['ended_at' => null],
                    ['started_at' => $refereeData->employment_date->toDateTimeString()]
                );

                // Update the status field to reflect employment
                $referee->update(['status' => EmploymentStatus::Employed]);
            }

            return $referee;
        });
    }
}
