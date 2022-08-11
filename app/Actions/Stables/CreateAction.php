<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\StableData;
use App\Models\Stable;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseStableAction
{
    use AsAction;

    /**
     * Create a stable.
     *
     * @param  \App\Data\StableData  $stableData
     * @return \App\Models\Stable
     */
    public function handle(StableData $stableData): Stable
    {
        /** @var \App\Models\Stable $stable */
        $stable = $this->stableRepository->create($stableData);

        if (isset($stableData->start_date)) {
            ActivateAction::run($stable, $stableData->start_date);
        }

        AddMembersAction::run($stable, $stableData->wrestlers, $stableData->tagTeams);

        return $stable;
    }
}
