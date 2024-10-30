<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Events\Wrestlers\WrestlerClearedFromInjury;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ClearInjuryAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Clear an injury of a wrestler.
     *
     * @throws \App\Exceptions\CannotBeClearedFromInjuryException
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        $this->ensureCanBeClearedFromInjury($wrestler);

        $recoveryDate ??= now();

        $this->wrestlerRepository->clearInjury($wrestler, $recoveryDate);

        event(new WrestlerClearedFromInjury($wrestler, $recoveryDate));
    }

    /**
     * Ensure a wrestler can be cleared from an injury.
     *
     * @throws \App\Exceptions\CannotBeClearedFromInjuryException
     */
    private function ensureCanBeClearedFromInjury(Wrestler $wrestler): void
    {
        if (! $wrestler->isInjured()) {
            throw CannotBeClearedFromInjuryException::notInjured();
        }
    }
}
