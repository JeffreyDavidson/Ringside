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
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    public function handle(Referee $referee, ?Carbon $reinstatementDate = null): void
    {
        $this->ensureCanBeReinstated($referee);

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
            throw CannotBeReinstatedException::unemployed();
        }

        if ($referee->isReleased()) {
            throw CannotBeReinstatedException::released();
        }

        if ($referee->hasFutureEmployment()) {
            throw CannotBeReinstatedException::hasFutureEmployment();
        }

        if ($referee->isInjured()) {
            throw CannotBeReinstatedException::injured();
        }

        if ($referee->isRetired()) {
            throw CannotBeReinstatedException::retired();
        }

        if ($referee->isBookable()) {
            throw CannotBeReinstatedException::bookable();
        }
    }
}
