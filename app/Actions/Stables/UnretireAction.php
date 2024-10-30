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
        $this->ensureCanBeUnretired($stable);

        $unretiredDate ??= now();

        $this->stableRepository->unretire($stable, $unretiredDate);
        $this->stableRepository->activate($stable, $unretiredDate);
    }

    /**
     * Ensure a stable can be unretired.
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    private function ensureCanBeUnretired(Stable $stable): void
    {
        if (! $stable->isRetired()) {
            throw CannotBeUnretiredException::notRetired();
        }
    }
}
