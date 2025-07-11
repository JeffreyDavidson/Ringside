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
     */
    public function handle(StableData $stableData): Stable
    {
        /** @var Stable $stable */
        $stable = $this->stableRepository->create($stableData);

        if (isset($stableData->start_date)) {
            resolve(ActivateAction::class)->handle($stable, $stableData->start_date);
        }

        resolve(AddMembersAction::class)->handle(
            $stable,
            $stableData->wrestlers,
            $stableData->tagTeams,
            $stableData->managers
        );

        return $stable;
    }
}
