<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stable;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseStableAction
{
    use AsAction;

    /**
     * Unretire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Carbon|null  $unretiredDate
     * @return void
     */
    public function handle(Stable $stable, ?Carbon $unretiredDate = null): void
    {
        $unretiredDate ??= now();

        $this->stableRepository->unretire($stable, $unretiredDate);
        $this->stableRepository->activate($stable, $unretiredDate);
    }
}
