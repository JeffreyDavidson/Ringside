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
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Data\StableData  $stableData
     * @return \App\Models\Stable
     */
    public function handle(Stable $stable, StableData $stableData): Stable
    {
        $this->stableRepository->update($stable, $stableData);

        if (isset($stableData->start_date)) {
            if ($stable->canBeActivated() || $stable->canHaveActivationStartDateChanged($stableData->start_date)) {
                ActivateAction::run($stable, $stableData->start_date);
            }
        }

        UpdateMembersAction::run($stable, $stableData->wrestlers, $stableData->tagTeams);

        return $stable;
    }
}
