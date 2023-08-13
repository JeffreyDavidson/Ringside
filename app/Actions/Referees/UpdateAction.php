<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Data\RefereeData;
use App\Models\Referee;
use Illuminate\Support\Carbon;
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

        if ($this->shouldBeEmployed($referee, $refereeData->start_date)) {
            $this->refereeRepository->employ($referee, $refereeData->start_date);
        }

        return $referee;
    }

    /**
     * Find out if the referee can be employed.
     */
    private function shouldBeEmployed(Referee $referee, ?Carbon $startDate): bool
    {
        if (is_null($startDate)) {
            return false;
        }

        if ($referee->isCurrentlyEmployed()) {
            return false;
        }

        return true;
    }
}
