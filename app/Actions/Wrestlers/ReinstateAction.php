<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\CannotBeReinstatedException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Reinstate a wrestler.
     *
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    public function handle(Wrestler $wrestler, ?Carbon $reinstatementDate = null): void
    {
        throw_if($wrestler->isUnemployed(), CannotBeReinstatedException::class, $wrestler.' is currently unemployed and cannot be reinstated.');
        throw_if($wrestler->isReleased(), CannotBeReinstatedException::class, $wrestler.' is currently released and cannot be reinstated.');
        throw_if($wrestler->hasFutureEmployment(), CannotBeReinstatedException::class, $wrestler.' has not been officially employed and cannot be reinstated.');
        throw_if($wrestler->isInjured(), CannotBeReinstatedException::class, $wrestler.' is injured and cannot be reinstated.');
        throw_if($wrestler->isBookable(), CannotBeReinstatedException::class, $wrestler.' is currently employed and cannot be reinstated.');
        throw_if($wrestler->isRetired(), CannotBeReinstatedException::class, $wrestler.' is currently retired and cannot be reinstated.');

        $reinstatementDate ??= now();

        $this->wrestlerRepository->reinstate($wrestler, $reinstatementDate);

        if ($wrestler->isAMemberOfCurrentTagTeam()) {
            $wrestler->currentTagTeam->save();
        }
    }
}
