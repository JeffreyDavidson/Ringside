<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Stable;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class DeactivateAction extends BaseStableAction
{
    use AsAction;

    /**
     * Deactivate a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Carbon|null  $deactivationDate
     * @return void
     */
    public function handle(Stable $stable, ?Carbon $deactivationDate = null): void
    {
        throw_unless($stable->canBeDeactivated(), CannotBeDeactivatedException::class);

        $deactivationDate ??= now();

        $this->stableRepository->deactivate($stable, $deactivationDate);
        $this->stableRepository->disassemble($stable, $deactivationDate);
    }
}
