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
     *
     * @return void
     */
    public function handle(Wrestler $wrestler): void
    {
        $releaseDate = now();

        if ($wrestler->isSuspended()) {
            $this->wrestlerRepository->reinstate($wrestler, $releaseDate);
        }

        if ($wrestler->isInjured()) {
            $this->wrestlerRepository->clearInjury($wrestler, $releaseDate);
        }

        $this->wrestlerRepository->release($wrestler, $releaseDate);
        $wrestler->save();

        if ($wrestler->currentTagTeam() !== null && $wrestler->currentTagTeam()->exists()) {
            $wrestler->currentTagTeam()->touch();
            $this->wrestlerRepository->removeFromCurrentTagTeam($wrestler, $releaseDate);
        }
    }
}
