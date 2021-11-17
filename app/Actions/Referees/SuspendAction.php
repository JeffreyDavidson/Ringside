<?php

namespace App\Actions\Referees;

use App\Models\Referee;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Suspend a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return void
     */
    public function handle(Referee $referee): void
    {
        $suspensionDate = now()->toDateTimeString();

        $this->refereeRepository->suspend($referee, $suspensionDate);
        $referee->updateStatus()->save();
    }
}
