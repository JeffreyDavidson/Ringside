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
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        throw_if($wrestler->isUnemployed(), CannotBeRetiredException::class, $wrestler.' is unemployed and cannot be retired.');
        throw_if($wrestler->hasFutureEmployment(), CannotBeRetiredException::class, $wrestler.' has not been officially employed and cannot be retired');
        throw_if($wrestler->isRetired(), CannotBeRetiredException::class, $wrestler.' is already retired.');

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

        event(new WrestlerRetired($wrestler));
    }
}
