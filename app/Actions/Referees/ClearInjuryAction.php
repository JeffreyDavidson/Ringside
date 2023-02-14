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
     *
     *
     * @throws \App\Exceptions\CannotBeClearedFromInjuryException
     */
    public function handle(Referee $referee, ?Carbon $recoveryDate = null): void
    {
        throw_if(! $referee->isInjured(), CannotBeClearedFromInjuryException::class, $referee->first_name.' '.$referee->last_name.' is not injured and cannot be cleared from an injury.');

        $recoveryDate ??= now();

        $this->refereeRepository->clearInjury($referee, $recoveryDate);
    }
}
