<?php

namespace App\Actions\Referees;

use App\Models\Referee;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Release a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return void
     */
    public function handle(Referee $referee): void
    {
        $releaseDate = now();

        if ($referee->isSuspended()) {
            $this->refereeRepository->reinstate($referee, $releaseDate);
        }

        if ($referee->isInjured()) {
            $this->refereeRepository->clearInjury($referee, $releaseDate);
        }

        $this->refereeRepository->release($referee, $releaseDate);
        $referee->save();
    }
}
