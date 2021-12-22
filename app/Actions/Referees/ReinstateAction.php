<?php

namespace App\Actions\Referees;

use App\Models\Referee;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Reinstate a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return void
     */
    public function handle(Referee $referee): void
    {
        $reinstatementDate = now()->toDateTimeString();

        $this->refereeRepository->reinstate($referee, $reinstatementDate);
        $referee->save();
    }
}
