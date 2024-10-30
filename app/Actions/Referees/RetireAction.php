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
        $this->ensureCanBeRetired($referee);

        $retirementDate ??= now();

        if ($referee->isSuspended()) {
            $this->refereeRepository->reinstate($referee, $retirementDate);
        }

        if ($referee->isInjured()) {
            $this->refereeRepository->clearInjury($referee, $retirementDate);
        }

        if ($referee->isCurrentlyEmployed()) {
            $this->refereeRepository->release($referee, $retirementDate);
        }

        $this->refereeRepository->retire($referee, $retirementDate);
    }

    /**
     * Ensure a referee can be retired.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    private function ensureCanBeRetired(Referee $referee): void
    {
        if ($referee->isUnemployed()) {
            throw CannotBeRetiredException::unemployed();
        }

        if ($referee->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment();
        }

        if ($referee->isRetired()) {
            throw CannotBeRetiredException::retired();
        }
    }
}
