<?php

namespace App\Services;

use App\Actions\Stables\ActivateAction;
use App\Actions\Stables\AddMembersAction;
use App\Actions\Stables\UpdateMembersAction;
use App\Data\StableData;
use App\Models\Stable;
use App\Repositories\StableRepository;

class StableService
{
    /**
     * The repository implementation.
     *
     * @var \App\Repositories\StableRepository
     */
    protected $stableRepository;

    /**
     * Create a new stable service instance.
     *
     * @param \App\Repositories\StableRepository $stableRepository
     */
    public function __construct(StableRepository $stableRepository)
    {
        $this->stableRepository = $stableRepository;
    }

    /**
     * Create a stable with given data.
     *
     * @param  \App\Data\StableData $stableData
     * @return \App\Models\Stable
     */
    public function create(StableData $stableData)
    {
        /** @var \App\Models\Stable $stable */
        $stable = $this->stableRepository->create($stableData);

        if (isset($stableData->start_date)) {
            ActivateAction::run($stable, $stableData->start_date);
        }

        AddMembersAction::run($stable, $stableData->wrestlers, $stableData->tagTeams);

        return $stable;
    }

    /**
     * Update a given stable with given data.
     *
     * @param  \App\Models\Stable $stable
     * @param  \App\Data\StableData $stableData
     * @return \App\Models\Stable
     */
    public function update(Stable $stable, StableData $stableData)
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

    /**
     * Delete a given stable.
     *
     * @param  \App\Models\Stable $stable
     * @return void
     */
    public function delete(Stable $stable)
    {
        $this->stableRepository->delete($stable);
    }

    /**
     * Restore a given stable.
     *
     * @param  \App\Models\Stable $stable
     * @return void
     */
    public function restore(Stable $stable)
    {
        $this->stableRepository->restore($stable);
    }
}
