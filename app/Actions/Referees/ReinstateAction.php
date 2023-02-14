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
     *
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    public function handle(Referee $referee, ?Carbon $reinstatementDate = null): void
    {
        throw_if(! $referee->isSuspended(), CannotBeReinstatedException::class, $referee.' is not suspended and cannot be reinstated.');

        $reinstatementDate ??= now();

        $this->refereeRepository->reinstate($referee, $reinstatementDate);
    }
}
