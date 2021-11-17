<?php

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class ClearInjuryAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Clear an injury of a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return void
     */
    public function handle(Wrestler $wrestler): void
    {
        $recoveryDate = now()->toDateTimeString();

        $this->wrestlerRepository->clearInjury($wrestler, $recoveryDate);
        $wrestler->updateStatus()->save();

        if (! is_null($wrestler->currentTagTeam) && $wrestler->currentTagTeam->exists()) {
            $wrestler->currentTagTeam->updateStatus()->save();
        }
    }
}
