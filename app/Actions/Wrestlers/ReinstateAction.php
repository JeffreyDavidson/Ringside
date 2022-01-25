<?php

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Reinstate a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     *
     * @return void
     */
    public function handle(Wrestler $wrestler): void
    {
        $reinstatementDate = now();

        $this->wrestlerRepository->reinstate($wrestler, $reinstatementDate);
        $wrestler->save();

        if ($wrestler->currentTagTeam() !== null && $wrestler->currentTagTeam()->exists()) {
            $wrestler->currentTagTeam()->touch();
        }
    }
}
