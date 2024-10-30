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
     *
     * @throws \App\Exceptions\CannotBeInjuredException
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
            throw CannotBeInjuredException::unemployed();
        }

        if ($referee->isReleased()) {
            throw CannotBeInjuredException::released();
        }

        if ($referee->isRetired()) {
            throw CannotBeInjuredException::retired();
        }

        if ($referee->hasFutureEmployment()) {
            throw CannotBeInjuredException::hasFutureEmployment();
        }

        if ($referee->isInjured()) {
            throw CannotBeInjuredException::injured();
        }

        if ($referee->isSuspended()) {
            throw CannotBeInjuredException::suspended();
        }
    }
}
