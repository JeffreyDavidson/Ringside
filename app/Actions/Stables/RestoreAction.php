<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseStableAction
{
    use AsAction;

    /**
     * Restore a soft-deleted stable.
     *
     * This handles the complete stable restoration workflow:
     * - Restores the soft-deleted stable record
     * - Makes the stable available for membership and storylines again
     * - Preserves all historical member relationships and match history
     * - Reactivates the stable for future operations
     *
     * @param  Stable  $stable  The soft-deleted stable to restore
     *
     * @example
     * ```php
     * $deletedStable = Stable::onlyTrashed()->find(1);
     * RestoreAction::run($deletedStable);
     * ```
     */
    public function handle(Stable $stable): void
    {
        $this->stableRepository->restore($stable);
    }
}
