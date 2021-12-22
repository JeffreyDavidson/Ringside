<?php

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Release a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return void
     */
    public function handle(Wrestler $wrestler): void
    {
        $releaseDate ??= now()->toDateTimeString();

        if ($wrestler->isSuspended()) {
            $this->wrestlerRepository->reinstate($wrestler, $releaseDate);
        }

        if ($wrestler->isInjured()) {
            $this->wrestlerRepository->clearInjury($wrestler, $releaseDate);
        }

        $this->wrestlerRepository->release($wrestler, $releaseDate);
        $wrestler->save();

        if (! is_null($wrestler->currentTagTeam) && $wrestler->currentTagTeam->exists()) {
            $wrestler->currentTagTeam->save();
            $this->wrestlerRepository->removeFromCurrentTagTeam($wrestler, $releaseDate);
        }
    }
}
