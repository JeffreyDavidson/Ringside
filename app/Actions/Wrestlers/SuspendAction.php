<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Events\Wrestlers\WrestlerSuspended;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Suspend a wrestler.
     */
    public function handle(Wrestler $wrestler, ?Carbon $suspensionDate = null): void
    {
        $this->ensureCanBeSuspended($wrestler);

        $suspensionDate ??= now();

        $this->wrestlerRepository->suspend($wrestler, $suspensionDate);

        event(new WrestlerSuspended($wrestler, $suspensionDate));
    }

    /**
     * Ensure a wrestler can be suspended.
     *
     * @throws \App\Exceptions\CannotBeSuspendedException
     */
    private function ensureCanBeSuspended(Wrestler $wrestler): void
    {
        if ($wrestler->isUnemployed()) {
            throw CannotBeSuspendedException::unemployed($wrestler);
        }

        if ($wrestler->isReleased()) {
            throw CannotBeSuspendedException::released($wrestler);
        }

        if ($wrestler->isRetired()) {
            throw CannotBeSuspendedException::retired($wrestler);
        }

        if ($wrestler->hasFutureEmployment()) {
            throw CannotBeSuspendedException::hasFutureEmployment($wrestler);
        }

        if ($wrestler->isSuspended()) {
            throw CannotBeSuspendedException::suspended($wrestler);
        }

        if ($wrestler->isInjured()) {
            throw CannotBeSuspendedException::injured($wrestler);
        }
    }
}
