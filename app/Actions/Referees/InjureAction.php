<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\CannotBeInjuredException;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Injure a referee.
     */
    public function handle(Referee $referee, ?Carbon $injureDate = null): void
    {
        $this->ensureCanBeInjured($referee);

        $injureDate ??= now();

        $this->refereeRepository->injure($referee, $injureDate);
    }

    /**
     * Ensure a referee can be injured.
     *
     * @throws \App\Exceptions\CannotBeInjuredException
     */
    private function ensureCanBeInjured(Referee $referee): void
    {
        if ($referee->isUnemployed()) {
            throw CannotBeInjuredException::unemployed($referee);
        }

        if ($referee->isReleased()) {
            throw CannotBeInjuredException::released($referee);
        }

        if ($referee->isRetired()) {
            throw CannotBeInjuredException::retired($referee);
        }

        if ($referee->hasFutureEmployment()) {
            throw CannotBeInjuredException::hasFutureEmployment($referee);
        }

        if ($referee->isInjured()) {
            throw CannotBeInjuredException::injured($referee);
        }

        if ($referee->isSuspended()) {
            throw CannotBeInjuredException::suspended($referee);
        }
    }
}
