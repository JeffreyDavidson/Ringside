<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Data\Referees\RefereeData;
use App\Models\Referees\Referee;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Create a referee.
     *
     * This handles the complete referee creation workflow:
     * - Creates the referee record with personal and professional details
     * - Creates employment record if employment_date is provided
     * - Establishes the referee as available for match officiating
     *
     * @param  RefereeData  $refereeData  The data transfer object containing referee information
     * @return Referee The newly created referee instance
     *
     * @example
     * ```php
     * $refereeData = new RefereeData([
     *     'name' => 'Earl Hebner',
     *     'hometown' => 'Richmond, VA',
     *     'employment_date' => now()
     * ]);
     * $referee = CreateAction::run($refereeData);
     * ```
     */
    public function handle(RefereeData $refereeData): Referee
    {
        return DB::transaction(function () use ($refereeData): Referee {
            $referee = $this->refereeRepository->create($refereeData);

            if (isset($refereeData->employment_date)) {
                $this->refereeRepository->createEmployment($referee, $refereeData->employment_date);
            }

            return $referee;
        });
    }
}
