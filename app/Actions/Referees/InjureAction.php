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
        throw_if($referee->isInjured(), CannotBeInjuredException::class, $referee.' is currently injured and cannot be injured further.');
        throw_if($referee->isUnemployed(), CannotBeInjuredException::class, $referee.' is currently unemployed and cannot be injured.');
        throw_if($referee->isSuspended(), CannotBeInjuredException::class, $referee.' is currently suspended and cannot be injured.');
        throw_if($referee->isReleased(), CannotBeInjuredException::class, $referee.' is currently released and cannot be injured.');
        throw_if($referee->hasFutureEmployment(), CannotBeInjuredException::class, $referee.' is has a future employment and cannot be injured.');
        throw_if($referee->isRetired(), CannotBeInjuredException::class, $referee.' is currently retired and cannot be injured.');

        $injureDate ??= now();

        $this->refereeRepository->injure($referee, $injureDate);
    }
}
