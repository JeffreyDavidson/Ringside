<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Suspend a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon|null  $suspensionDate
     * @return void
     */
    public function handle(Referee $referee, ?Carbon $suspensionDate = null): void
    {
        $suspensionDate ??= now();

        $this->refereeRepository->suspend($referee, $suspensionDate);
    }
}
