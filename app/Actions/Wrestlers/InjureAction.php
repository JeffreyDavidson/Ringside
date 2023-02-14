<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\CannotBeInjuredException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Injure a wrestler.
     *
     *
     * @throws \App\Exceptions\CannotBeInjuredException
     */
    public function handle(Wrestler $wrestler, ?Carbon $injureDate = null): void
    {
        throw_if($wrestler->isUnemployed(), CannotBeInjuredException::class, $wrestler.' is unemployed and cannot be injured.');
        throw_if($wrestler->isReleased(), CannotBeInjuredException::class, $wrestler.' is released and cannot be injured.');
        throw_if($wrestler->isRetired(), CannotBeInjuredException::class, $wrestler.' is retired and cannot be injured.');
        throw_if($wrestler->hasFutureEmployment(), CannotBeInjuredException::class, $wrestler.' has not been officially employed and cannot be injured.');
        throw_if($wrestler->isInjured(), CannotBeInjuredException::class, $wrestler.' is already currently injured.');
        throw_if($wrestler->isSuspended(), CannotBeInjuredException::class, $wrestler.' is suspended and cannot be injured.');

        $injureDate ??= now();

        $this->wrestlerRepository->injure($wrestler, $injureDate);

        if ($wrestler->isAMemberOfCurrentTagTeam()) {
            $wrestler->currentTagTeam->save();
        }
    }
}
