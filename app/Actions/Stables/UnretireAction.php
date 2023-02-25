<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Stable;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseStableAction
{
    use AsAction;

    /**
     * Unretire a stable.
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    public function handle(Stable $stable, ?Carbon $unretiredDate = null): void
    {
        throw_if($stable->canBeUnretired(), CannotBeUnretiredException::class);

        $unretiredDate ??= now();

        $this->stableRepository->unretire($stable, $unretiredDate);
        $this->stableRepository->activate($stable, $unretiredDate);
    }
}
