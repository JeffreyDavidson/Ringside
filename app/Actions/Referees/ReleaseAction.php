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
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    public function handle(Referee $referee, ?Carbon $releaseDate = null): void
    {
        $this->ensureCanBeReleased($referee);

        $releaseDate ??= now();

        if ($referee->isSuspended()) {
            $this->refereeRepository->reinstate($referee, $releaseDate);
        }

        if ($referee->isInjured()) {
            $this->refereeRepository->clearInjury($referee, $releaseDate);
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
            throw CannotBeReleasedException::unemployed();
        }

        if ($referee->isReleased()) {
            throw CannotBeReleasedException::released();
        }

        if ($referee->hasFutureEmployment()) {
            throw CannotBeReleasedException::hasFutureEmployment();
        }

        if ($referee->isRetired()) {
            throw CannotBeReleasedException::retired();
        }
    }
}
