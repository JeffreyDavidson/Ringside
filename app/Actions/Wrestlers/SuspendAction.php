<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Suspend a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return void
     */
    public function handle(Wrestler $wrestler): void
    {
        $suspensionDate = now();

        $this->wrestlerRepository->suspend($wrestler, $suspensionDate);
        $wrestler->save();

        if ($wrestler->currentTagTeam() !== null && $wrestler->currentTagTeam()->exists()) {
            $wrestler->currentTagTeam()->touch();
        }
    }
}
