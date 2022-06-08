<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ClearInjuryAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Clear an injury of a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon|null $recoveryDate
     * @return void
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        $recoveryDate ??= now();

        $this->wrestlerRepository->clearInjury($wrestler, $recoveryDate);
        $wrestler->save();

        if ($wrestler->isAMemberOfCurrentTagTeam()) {
            $wrestler->currentTagTeam->save();
        }
    }
}
