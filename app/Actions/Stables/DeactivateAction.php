<?php

namespace App\Actions\Stables;

use App\Models\Stable;
use Lorisleiva\Actions\Concerns\AsAction;

class DeactivateAction extends BaseStableAction
{
    use AsAction;

    /**
     * Deactivate a stable.
     *
     * @param  \App\Models\Stable  $stable
     *
     * @return void
     */
    public function handle(Stable $stable): void
    {
        $deactivationDate = now();

        $this->stableRepository->deactivate($stable, $deactivationDate);
        $this->stableRepository->disassemble($stable, $deactivationDate);
        $stable->save();
    }
}
