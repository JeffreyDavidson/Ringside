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
     */
    public function handle(Wrestler $wrestler, ?Carbon $injureDate = null): void
    {
        $this->ensureCanBeInjured($wrestler);

        $injureDate ??= now();

        $this->wrestlerRepository->injure($wrestler, $injureDate);

        event(new WrestlerInjured($wrestler));
    }

    /**
     * Ensure a wrestler can be injured.
     *
     * @throws \App\Exceptions\CannotBeInjuredException
     */
    private function ensureCanBeInjured(Wrestler $wrestler): void
    {
        if ($wrestler->isUnemployed()) {
            throw CannotBeInjuredException::unemployed($wrestler);
        }

        if ($wrestler->isReleased()) {
            throw CannotBeInjuredException::released($wrestler);
        }

        if ($wrestler->isRetired()) {
            throw CannotBeInjuredException::retired($wrestler);
        }

        if ($wrestler->hasFutureEmployment()) {
            throw CannotBeInjuredException::hasFutureEmployment($wrestler);
        }

        if ($wrestler->isInjured()) {
            throw CannotBeInjuredException::injured($wrestler);
        }

        if ($wrestler->isSuspended()) {
            throw CannotBeInjuredException::suspended($wrestler);
        }
    }
}
