<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Data\Referees\RefereeData;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseRefereeAction
{
    use AsAction;

    public function __construct(
        RefereeRepository $refereeRepository
    ) {
        parent::__construct($refereeRepository);
    }

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
            $this->refereeRepository->update($referee, $refereeData);

            // Track if referee was just employed
            $wasEmployed = $referee->isEmployed();

            // Create employment record if employment_date is provided and referee is eligible
            if (! is_null($refereeData->employment_date) && ! $referee->isEmployed()) {
                $this->refereeRepository->createEmployment($referee, $refereeData->employment_date);
            }

            return $referee;
        });
    }
}
