<?php

namespace App\Actions\Stables;

use App\Models\Stable;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseStableAction
{
    use AsAction;

    /**
     * Unretire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return void
     */
    public function handle(Stable $stable): void
    {
        $unretiredDate = now();

        $this->stableRepository->unretire($stable, $unretiredDate);
        $this->stableRepository->activate($stable, $unretiredDate);
        $stable->save();
    }
}
