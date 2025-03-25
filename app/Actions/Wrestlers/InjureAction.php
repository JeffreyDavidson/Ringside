<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Events\Wrestlers\WrestlerInjured;
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
     * @throws \App\Exceptions\CannotBeInjuredException
     */
    public function handle(Wrestler $wrestler, ?Carbon $injureDate = null): void
    {
        $this->ensureCanBeInjured($wrestler);

        $injureDate ??= now();

        $this->wrestlerRepository->injure($wrestler, $injureDate);

        event(new WrestlerInjured($wrestler, $injureDate));
    }

    /**
     * Ensure a wrestler can be injured.
     *
     * @throws \App\Exceptions\CannotBeInjuredException
     */
    private function ensureCanBeInjured(Wrestler $wrestler): void
    {
        if ($wrestler->isUnemployed()) {
            throw CannotBeInjuredException::unemployed();
        }

        if ($wrestler->isReleased()) {
            throw CannotBeInjuredException::released();
        }

        if ($wrestler->isRetired()) {
            throw CannotBeInjuredException::retired();
        }

        if ($wrestler->hasFutureEmployment()) {
            throw CannotBeInjuredException::hasFutureEmployment();
        }

        if ($wrestler->isInjured()) {
            throw CannotBeInjuredException::injured();
        }

        if ($wrestler->isSuspended()) {
            throw CannotBeInjuredException::suspended();
        }
    }
}
