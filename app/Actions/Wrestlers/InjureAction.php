<?php

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Injure a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return void
     */
    public function handle(Wrestler $wrestler): void
    {
        $injureDate = now()->toDateTimeString();

        $this->wrestlerRepository->injure($wrestler, $injureDate);
        $wrestler->save();

        if (! is_null($wrestler->currentTagTeam) && $wrestler->currentTagTeam->exists()) {
            $wrestler->currentTagTeam->save();
        }
    }
}
