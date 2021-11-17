<?php

namespace App\Actions\Referees;

use App\Models\Referee;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Unretire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return void
     */
    public function handle(Referee $referee): void
    {
        $employmentDate = now()->toDateTimeString();

        $this->refereeRepository->employ($referee, $employmentDate);
        $referee->updateStatus()->save();
    }
}
