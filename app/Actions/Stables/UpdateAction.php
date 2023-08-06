<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\StableData;
use App\Models\Stable;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseStableAction
{
    use AsAction;

    /**
     * Update a stable.
     */
    public function handle(Stable $stable, StableData $stableData): Stable
    {
        $this->stableRepository->update($stable, $stableData);

        if (isset($stableData->start_date) && $this->ensureStartDateCanBeChanged($stable)) {
            ActivateAction::run($stable, $stableData->start_date);
        }

        UpdateMembersAction::run($stable, $stableData->wrestlers, $stableData->tagTeams);

        return $stable;
    }

    /**
     * Ensure a stable's start date can be changed.
     */
    private function ensureStartDateCanBeChanged(Stable $stable): bool
    {
        if ($stable->isUnactivated() || $stable->hasFutureActivation()) {
            return true;
        }

        // Add check on start date from request

        return false;
    }
}
