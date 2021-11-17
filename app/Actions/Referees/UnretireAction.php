<?php

namespace App\Actions\Referees;

use App\Models\Referee;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseRefereeAction
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
        $unretiredDate = now()->toDateTimeString();

        $this->refereeRepository->unretire($referee, $unretiredDate);
        $this->refereeRepository->employ($referee, $unretiredDate);
        $referee->updateStatus()->save();
    }
}
