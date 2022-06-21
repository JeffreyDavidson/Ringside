<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ClearInjuryAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Clear an injury of a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon|null  $recoveryDate
     * @return void
     */
    public function handle(Referee $referee, ?Carbon $recoveryDate = null): void
    {
        $recoveryDate ??= now();

        $this->refereeRepository->clearInjury($referee, $recoveryDate);
    }
}
