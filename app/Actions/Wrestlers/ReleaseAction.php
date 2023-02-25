<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\CannotBeReleasedException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Release a wrestler.
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        throw_if($wrestler->isUnemployed(), CannotBeReleasedException::class, $wrestler.' is unemployed and cannot be released.');
        throw_if($wrestler->isReleased(), CannotBeReleasedException::class, $wrestler.' is already released.');
        throw_if($wrestler->hasFutureEmployment(), CannotBeReleasedException::class, $wrestler.' has not been officially employed and cannot be released.');
        throw_if($wrestler->isRetired(), CannotBeReleasedException::class, $wrestler.' has is retired and cannot be released.');

        $releaseDate ??= now();

        if ($wrestler->isSuspended()) {
            ReinstateAction::run($wrestler, $releaseDate);
        }

        if ($wrestler->isInjured()) {
            ClearInjuryAction::run($wrestler, $releaseDate);
        }

        $this->wrestlerRepository->release($wrestler, $releaseDate);

        if ($wrestler->isAMemberOfCurrentTagTeam()) {
            $wrestler->currentTagTeam->save();
            $this->wrestlerRepository->removeFromCurrentTagTeam($wrestler, $releaseDate);
        }
    }
}
