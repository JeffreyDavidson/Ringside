<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Release a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon|null $releaseDate
     * @return void
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        $releaseDate ??= now();

        if ($wrestler->isSuspended()) {
            ReinstateAction::run($wrestler, $releaseDate);
        }

        if ($wrestler->isInjured()) {
            ClearInjuryAction::run($wrestler, $releaseDate);
        }

        $this->wrestlerRepository->release($wrestler, $releaseDate);
        $wrestler->save();

        if ($wrestler->currentTagTeam() !== null && $wrestler->currentTagTeam()->exists()) {
            $wrestler->currentTagTeam()->touch();
            $this->wrestlerRepository->removeFromCurrentTagTeam($wrestler, $releaseDate);
        }
    }
}
