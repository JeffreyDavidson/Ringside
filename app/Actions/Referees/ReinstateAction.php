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
     */
    public function handle(Referee $referee, ?Carbon $reinstatementDate = null): void
    {
        $this->ensureCanBeReinstated($referee);

        throw_if(! $referee->isSuspended(), CannotBeReinstatedException::class, $referee.' is not suspended and cannot be reinstated.');

        $reinstatementDate ??= now();

        $this->refereeRepository->reinstate($referee, $reinstatementDate);
    }

    /**
     * Ensure a referee can be reinstated.
     *
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    private function ensureCanBeReinstated(Referee $referee): void
    {
        if ($referee->isUnemployed()) {
            throw CannotBeReinstatedException::unemployed($referee);
        }

        if ($referee->isReleased()) {
            throw CannotBeReinstatedException::released($referee);
        }

        if ($referee->hasFutureEmployment()) {
            throw CannotBeReinstatedException::hasFutureEmployment($referee);
        }

        if ($referee->isInjured()) {
            throw CannotBeReinstatedException::injured($referee);
        }

        if ($referee->isRetired()) {
            throw CannotBeReinstatedException::retired($referee);
        }

        if ($referee->isBookable()) {
            throw CannotBeReinstatedException::bookable($referee);
        }
    }
}
