<?php

namespace App\Actions\Referees;

use App\Models\Referee;
use Lorisleiva\Actions\Concerns\AsAction;

class ClearInjuryAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Clear an injury of a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return void
     */
    public function handle(Referee $referee): void
    {
        $this->refereeRepository->clearInjury($referee, now());
        $referee->save();
    }
}
