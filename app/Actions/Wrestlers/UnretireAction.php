<?php

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Unretire a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return void
     */
    public function handle(Wrestler $wrestler): void
    {
        $unretiredDate = now()->toDateTimeString();

        $this->wrestlerRepository->unretire($wrestler, $unretiredDate);
        $this->wrestlerRepository->employ($wrestler, $unretiredDate);
        $wrestler->updateStatus()->save();
    }
}
