<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Data\RefereeData;
use App\Models\Referee;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Create a referee.
     */
    public function handle(RefereeData $refereeData): Referee
    {
        /** @var Referee $referee */
        $referee = $this->refereeRepository->create($refereeData);

        if (isset($refereeData->start_date)) {
            $this->refereeRepository->employ($referee, $refereeData->start_date);
        }

        return $referee;
    }
}
