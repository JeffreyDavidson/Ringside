<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Events\Wrestlers\WrestlerReinstated;
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
        $this->ensureCanBeReinstated($wrestler);

        $reinstatementDate ??= now();

        $this->wrestlerRepository->reinstate($wrestler, $reinstatementDate);

        event(new WrestlerReinstated($wrestler, $reinstatementDate));
    }

    /**
     * Ensure a wrestler can be reinstated.
     *
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    private function ensureCanBeReinstated(Wrestler $wrestler): void
    {
        if ($wrestler->isUnemployed()) {
            throw CannotBeReinstatedException::unemployed();
        }

        if ($wrestler->isReleased()) {
            throw CannotBeReinstatedException::released();
        }

        if ($wrestler->hasFutureEmployment()) {
            throw CannotBeReinstatedException::hasFutureEmployment();
        }

        if ($wrestler->isInjured()) {
            throw CannotBeReinstatedException::injured();
        }

        if ($wrestler->isRetired()) {
            throw CannotBeReinstatedException::retired();
        }

        if ($wrestler->isBookable()) {
            throw CannotBeReinstatedException::bookable();
        }
    }
}
