<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\CannotBeReleasedException;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Release a referee.
     */
    public function handle(Referee $referee, ?Carbon $releaseDate = null): void
    {
        $this->ensureCanBeReleased($referee);

        $releaseDate ??= now();

        if ($referee->isSuspended()) {
            ReinstateAction::run($referee, $releaseDate);
        }

        if ($referee->isInjured()) {
            ClearInjuryAction::run($referee, $releaseDate);
        }

        $this->refereeRepository->release($referee, $releaseDate);
    }

    /**
     * Ensure a referee can be released.
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    private function ensureCanBeReleased(Referee $referee): void
    {
        if ($referee->isUnemployed()) {
            throw CannotBeReleasedException::unemployed($referee);
        }

        if ($referee->isReleased()) {
            throw CannotBeReleasedException::released($referee);
        }

        if ($referee->hasFutureEmployment()) {
            throw CannotBeReleasedException::hasFutureEmployment($referee);
        }

        if ($referee->isRetired()) {
            throw CannotBeReleasedException::retired($referee);
        }
    }
}
