<?php

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Employ a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Carbon\Carbon|null  $startDate
     * @return void
     */
    public function handle(Wrestler $wrestler, ?Carbon $startDate = null): void
    {
        $startDate ??= now();

        $this->wrestlerRepository->employ($wrestler, $startDate);
        $wrestler->save();
    }
}
