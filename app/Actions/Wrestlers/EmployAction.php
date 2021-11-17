<?php

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseWrestlerAction
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
        $employmentDate = now()->toDateTimeString();

        $this->wrestlerRepository->employ($wrestler, $employmentDate);
        $wrestler->updateStatus()->save();
    }
}
