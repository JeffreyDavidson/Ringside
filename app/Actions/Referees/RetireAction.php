<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Retire a referee.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    public function handle(Referee $referee, ?Carbon $retirementDate = null): void
    {
        throw_if($referee->isUnemployed(), CannotBeRetiredException::class, $referee.' is unemployed and cannot be retired.');
        throw_if($referee->hasFutureEmployment(), CannotBeRetiredException::class, $referee.' has not been officially employed and cannot be retired');
        throw_if($referee->isRetired(), CannotBeRetiredException::class, $referee.' is already retired.');
        throw_if($referee->isReleased(), CannotBeRetiredException::class, $referee.' was released and cannot be retired. Re-employ this referee to retire them.');

        $retirementDate ??= now();

        if ($referee->isSuspended()) {
            ReinstateAction::run($referee, $retirementDate);
        }

        if ($referee->isInjured()) {
            ClearInjuryAction::run($referee, $retirementDate);
        }

        ReleaseAction::run($referee, $retirementDate);

        $this->refereeRepository->retire($referee, $retirementDate);
    }
}
