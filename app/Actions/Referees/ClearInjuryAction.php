<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ClearInjuryAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Clear an injury of a referee.
     */
    public function handle(Referee $referee, ?Carbon $recoveryDate = null): void
    {
        $this->ensureCanBeClearedFromInjury($referee);

        $recoveryDate ??= now();

        $this->refereeRepository->clearInjury($referee, $recoveryDate);
    }

    /**
     * Ensure a referee can be cleared from an injury.
     *
     * @throws \App\Exceptions\CannotBeClearedFromInjuryException
     */
    private function ensureCanBeClearedFromInjury(Referee $referee): void
    {
        if (! $referee->isInjured()) {
            throw CannotBeClearedFromInjuryException::notInjured($referee);
        }
    }
}
