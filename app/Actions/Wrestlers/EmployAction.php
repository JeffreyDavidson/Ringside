<?php

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Employ a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Carbon\Carbon|null  $employmentDate
     *
     * @return void
     */
    public function handle(Wrestler $wrestler, $employmentDate = null): void
    {
        $employmentDate ??= now();

        $this->wrestlerRepository->employ($wrestler, $employmentDate);
        $wrestler->save();
    }
}
