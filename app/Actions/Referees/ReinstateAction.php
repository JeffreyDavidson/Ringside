<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\CannotBeReinstatedException;
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
     *
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    public function handle(Referee $referee, ?Carbon $reinstatementDate = null): void
    {
        throw_if($referee->canBeReinstated(), CannotBeReinstatedException::class);

        $reinstatementDate ??= now();

        $this->refereeRepository->reinstate($referee, $reinstatementDate);
    }
}
