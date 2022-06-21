<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Reinstate a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon|null  $reinstatementDate
     * @return void
     */
    public function handle(Referee $referee, ?Carbon $reinstatementDate = null): void
    {
        $reinstatementDate ??= now();

        $this->refereeRepository->reinstate($referee, $reinstatementDate);
    }
}
