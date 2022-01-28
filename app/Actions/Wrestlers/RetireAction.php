<?php

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Retire a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Carbon\Carbon|null  $retirementDate
     *
     * @return void
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        $retirementDate ??= now();

        if ($wrestler->isSuspended()) {
            ReinstateAction::run($wrestler, $retirementDate);
        }

        if ($wrestler->isInjured()) {
            ClearInjuryAction::run($wrestler, $retirementDate);
        }

        $this->wrestlerRepository->release($wrestler, $retirementDate);
        $this->wrestlerRepository->retire($wrestler, $retirementDate);
        $wrestler->save();

        if ($wrestler->currentTagTeam() !== null && $wrestler->currentTagTeam()->exists()) {
            $wrestler->currentTagTeam()->touch();
        }
    }
}
