<?php

declare(strict_types=1);

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
        $injureDate = now();

        $this->wrestlerRepository->injure($wrestler, $injureDate);
        $wrestler->save();

        if ($wrestler->isAMemberOfCurrentTagTeam()) {
            $wrestler->currentTagTeam->save();
        }
    }
}
