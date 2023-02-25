<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Data\RefereeData;
use App\Models\Referee;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Update a referee.
     */
    public function handle(Referee $referee, RefereeData $refereeData): Referee
    {
        $this->refereeRepository->update($referee, $refereeData);

        if (isset($refereeData->start_date)) {
            if ($referee->canBeEmployed()
                || $referee->canHaveEmploymentStartDateChanged($refereeData->start_date)
            ) {
                EmployAction::run($referee, $refereeData->start_date);
            }
        }

        return $referee;
    }
}
