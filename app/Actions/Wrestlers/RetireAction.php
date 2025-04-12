<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Events\Wrestlers\WrestlerRetired;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

final class RetireAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Retire a wrestler.
     *
     * @throws CannotBeRetiredException
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        $this->ensureCanBeRetired($wrestler);

        $retirementDate ??= now();

        if ($wrestler->isSuspended()) {
            $this->wrestlerRepository->reinstate($wrestler, $retirementDate);
        }

        if ($wrestler->isInjured()) {
            $this->wrestlerRepository->clearInjury($wrestler, $retirementDate);
        }

        if ($wrestler->isCurrentlyEmployed()) {
            $this->wrestlerRepository->release($wrestler, $retirementDate);
        }

        $this->wrestlerRepository->retire($wrestler, $retirementDate);

        event(new WrestlerRetired($wrestler, $retirementDate));
    }

    /**
     * Ensure a wrestler can be retired.
     *
     * @throws CannotBeRetiredException
     */
    private function ensureCanBeRetired(Wrestler $wrestler): void
    {
        if ($wrestler->isUnemployed()) {
            throw CannotBeRetiredException::unemployed();
        }

        if ($wrestler->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment();
        }

        if ($wrestler->isRetired()) {
            throw CannotBeRetiredException::retired();
        }
    }
}
