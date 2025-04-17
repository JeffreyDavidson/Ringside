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
     *
     * @throws CannotBeSuspendedException
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
     * @throws CannotBeSuspendedException
     */
    private function ensureCanBeSuspended(Wrestler $wrestler): void
    {
        if ($wrestler->isUnemployed()) {
            throw CannotBeSuspendedException::unemployed();
        }

        if ($wrestler->isReleased()) {
            throw CannotBeSuspendedException::released();
        }

        if ($wrestler->isRetired()) {
            throw CannotBeSuspendedException::retired();
        }

        if ($wrestler->hasFutureEmployment()) {
            throw CannotBeSuspendedException::hasFutureEmployment();
        }

        if ($wrestler->isSuspended()) {
            throw CannotBeSuspendedException::suspended();
        }

        if ($wrestler->isInjured()) {
            throw CannotBeSuspendedException::injured();
        }
    }
}
