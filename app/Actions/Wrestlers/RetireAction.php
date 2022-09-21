<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

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
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon|null  $retirementDate
     * @return void
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        throw_if($wrestler->isUnemployed(), CannotBeRetiredException::class, $wrestler.' is unemployed and cannot be retired.');
        throw_if($wrestler->hasFutureEmployment(), CannotBeRetiredException::class, $wrestler.' has not been officially employed and cannot be retired');
        throw_if($wrestler->isRetired(), CannotBeRetiredException::class, $wrestler.' is already retired.');
        throw_if($wrestler->isReleased(), CannotBeRetiredException::class, $wrestler.' was released and cannot be retired. Re-employ this wrestler to retire them.');

        $retirementDate ??= now();

        if ($wrestler->isSuspended()) {
            ReinstateAction::run($wrestler, $retirementDate);
        }

        if ($wrestler->isInjured()) {
            ClearInjuryAction::run($wrestler, $retirementDate);
        }

        ReleaseAction::run($wrestler, $retirementDate);

        $this->wrestlerRepository->retire($wrestler, $retirementDate);

        if ($wrestler->isAMemberOfCurrentTagTeam()) {
            $wrestler->currentTagTeam->save();
        }
    }
}
