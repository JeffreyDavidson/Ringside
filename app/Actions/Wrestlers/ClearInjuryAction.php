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
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        throw_if(! $wrestler->isInjured(), CannotBeClearedFromInjuryException::class, $wrestler->name.' is not currently injured so cannot be cleared from injury.');

        $recoveryDate ??= now();

        $this->wrestlerRepository->clearInjury($wrestler, $recoveryDate);

        event(new WrestlerClearedFromInjury($wrestler));
    }
}
