<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    /**
     * Delete a stable.
     *
     * The stable must already be inactive and have no current members. Those
     * transitions remain explicit operations so deletion only changes record state.
     *
     * @param  Stable  $stable  The stable to delete
     */
    public function handle(Stable $stable): void
    {
        DB::transaction(function () use ($stable): void {
            $lockedStable = Stable::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($stable->getKey());

            $lockedStable->ensureCanBeDeleted();
            $lockedStable->delete();
        });
    }
}
