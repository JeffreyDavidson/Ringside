<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Events\Wrestlers\WrestlerRetired;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Retire a wrestler.
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        $this->ensureCanBeRetired($wrestler);

        $retirementDate ??= now();

        if ($wrestler->isSuspended()) {
            ReinstateAction::run($wrestler, $retirementDate);
        }

        if ($wrestler->isInjured()) {
            ClearInjuryAction::run($wrestler, $retirementDate);
        }

        if ($wrestler->isCurrentlyEmployed()) {
            ReleaseAction::run($wrestler, $retirementDate);
        }

        $this->wrestlerRepository->retire($wrestler, $retirementDate);

        event(new WrestlerRetired($wrestler, $retirementDate));
    }

    /**
     * Ensure a wrestler can be retired.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    private function ensureCanBeRetired(Wrestler $wrestler): void
    {
        if ($wrestler->isUnemployed()) {
            throw CannotBeRetiredException::unemployed($wrestler);
        }

        if ($wrestler->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment($wrestler);
        }

        if ($wrestler->isRetired()) {
            throw CannotBeRetiredException::retired($wrestler);
        }
    }
}
