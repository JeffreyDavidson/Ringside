<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Suspend a referee.
     */
    public function handle(Referee $referee, ?Carbon $suspensionDate = null): void
    {
        $this->ensureCanBeSuspended($referee);

        $suspensionDate ??= now();

        $this->refereeRepository->suspend($referee, $suspensionDate);
    }

    /**
     * Ensure a referee can be suspended.
     *
     * @throws \App\Exceptions\CannotBeSuspendedException
     */
    private function ensureCanBeSuspended(Referee $referee): void
    {
        if ($referee->isUnemployed()) {
            throw CannotBeSuspendedException::unemployed($referee);
        }

        if ($referee->isReleased()) {
            throw CannotBeSuspendedException::released($referee);
        }

        if ($referee->isRetired()) {
            throw CannotBeSuspendedException::retired($referee);
        }

        if ($referee->hasFutureEmployment()) {
            throw CannotBeSuspendedException::hasFutureEmployment($referee);
        }

        if ($referee->isSuspended()) {
            throw CannotBeSuspendedException::suspended($referee);
        }

        if ($referee->isInjured()) {
            throw CannotBeSuspendedException::injured($referee);
        }
    }
}
