<?php

declare(strict_types=1);

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
        $suspensionDate = now();

        $this->refereeRepository->suspend($referee, $suspensionDate);
        $referee->save();
    }
}
