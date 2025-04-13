<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\StableData;
use App\Models\Stable;
use Lorisleiva\Actions\Concerns\AsAction;

final class UpdateAction extends BaseStableAction
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

        UpdateMembersAction::run(
            $stable,
            $stableData->wrestlers,
            $stableData->tagTeams,
            $stableData->managers
        );

        return $stable;
    }

    /**
     * Ensure a stable's start date can be changed.
     */
    private function ensureStartDateCanBeChanged(Stable $stable): bool
    {
        // Add check on start date from request
        if ($stable->isUnactivated()) {
            return true;
        }

        return $stable->hasFutureActivation();
    }
}
