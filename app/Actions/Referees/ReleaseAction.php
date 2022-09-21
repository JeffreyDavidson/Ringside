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
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon|null  $releaseDate
     * @return void
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    public function handle(Referee $referee, ?Carbon $releaseDate = null): void
    {
        throw_if($referee->canBeReleased(), CannotBeReleasedException::class);

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
