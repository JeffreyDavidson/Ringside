<?php

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Reinstate a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Carbon\Carbon|null $reinstatementDate
     *
     * @return void
     */
    public function handle(Wrestler $wrestler, ?Carbon $reinstatementDate = null): void
    {
        $reinstatementDate ??= now();

        $this->wrestlerRepository->reinstate($wrestler, $reinstatementDate);
        $wrestler->save();

        if ($wrestler->currentTagTeam() !== null && $wrestler->currentTagTeam()->exists()) {
            $wrestler->currentTagTeam()->touch();
        }
    }
}
