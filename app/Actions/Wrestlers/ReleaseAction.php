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
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon|null  $releaseDate
     * @return void
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        throw_unless($wrestler->canBeReleased(), CannotBeReleasedException::class);

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
