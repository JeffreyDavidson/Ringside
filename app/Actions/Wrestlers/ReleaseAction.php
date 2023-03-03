<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Events\Wrestlers\WrestlerReleased;
use App\Exceptions\CannotBeReleasedException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Release a wrestler.
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        $this->ensureCanBeReleased($wrestler);

        $releaseDate ??= now();

        if ($wrestler->isSuspended()) {
            ReinstateAction::run($wrestler, $releaseDate);
        }

        if ($wrestler->isInjured()) {
            ClearInjuryAction::run($wrestler, $releaseDate);
        }

        $this->wrestlerRepository->release($wrestler, $releaseDate);

        event(new WrestlerReleased($wrestler, $releaseDate));
    }

    /**
     * Ensure a wrestler can be released.
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    private function ensureCanBeReleased(Wrestler $wrestler): void
    {
        if ($wrestler->isUnemployed()) {
            throw CannotBeReleasedException::unemployed($wrestler);
        }

        if ($wrestler->isReleased()) {
            throw CannotBeReleasedException::released($wrestler);
        }

        if ($wrestler->hasFutureEmployment()) {
            throw CannotBeReleasedException::hasFutureEmployment($wrestler);
        }

        if ($wrestler->isRetired()) {
            throw CannotBeReleasedException::retired($wrestler);
        }
    }
}
