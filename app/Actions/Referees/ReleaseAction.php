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
        throw_if($referee->isUnemployed(), CannotBeReleasedException::class, $referee.' is unemployed and cannot be released.');
        throw_if($referee->isReleased(), CannotBeReleasedException::class, $referee.' is already released.');
        throw_if($referee->hasFutureEmployment(), CannotBeReleasedException::class, $referee.' has not been officially employed and cannot be released.');
        throw_if($referee->isRetired(), CannotBeReleasedException::class, $referee.' has is retired and cannot be released.');

        $releaseDate ??= now();

        if ($referee->isSuspended()) {
            ReinstateAction::run($referee, $releaseDate);
        }

        if ($referee->isInjured()) {
            ClearInjuryAction::run($referee, $releaseDate);
        }

        $this->refereeRepository->release($referee, $releaseDate);
    }
}
