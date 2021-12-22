<?php

namespace App\Actions\Referees;

use App\Models\Referee;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Retire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return void
     */
    public function handle(Referee $referee): void
    {
        $retirementDate = now()->toDateTimeString();

        if ($referee->isSuspended()) {
            $this->refereeRepository->reinstate($referee, $retirementDate);
        }

        if ($referee->isInjured()) {
            $this->refereeRepository->clearInjury($referee, $retirementDate);
        }

        $this->refereeRepository->release($referee, $retirementDate);
        $this->refereeRepository->retire($referee, $retirementDate);
        $referee->save();
    }
}
