<?php

namespace App\Services;

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
     * @param  array $data
     * @return \App\Models\Stable $stable
     */
    public function create(array $data)
    {
        $stable = $this->stableRepository->create($data);

        if (isset($data['started_at'])) {
            $this->stableRepository->activate($stable, $data['started_at']);
        }

        $this->addMembers($stable, $data['wrestlers'], $data['tag_teams']);

        return $stable;
    }

    /**
     * Update a given stable with given data.
     *
     * @param  \App\Models\Stable $stable
     * @param  array $data
     * @return \App\Models\Stable $stable
     */
    public function update(Stable $stable, array $data)
    {
        $this->stableRepository->update($stable, $data);

        if (isset($data['started_at'])) {
            $this->activateOrUpdateActivation($stable, $data['started_at']);
        }

        $this->updateMembers($stable, $data['wrestlers'], $data['tag_teams']);

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

    /**
     * Add members to a given stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  array|null $wrestlerIds
     * @param  array|null $tagTeamIds
     * @param  string|null $joinedDate
     * @return \App\Models\Stable $stable
     */
    private function addMembers(Stable $stable, array $wrestlerIds = null, array $tagTeamIds = null, string $joinedDate = null)
    {
        $joinedDate ??= now();

        if ($wrestlerIds) {
            $this->stableRepository->addWrestlers($stable, $wrestlerIds, $joinedDate);
        }

        if ($tagTeamIds) {
            $this->stableRepository->addTagTeams($stable, $tagTeamIds, $joinedDate);
        }

        return $stable;
    }

    /**
     * Update the members of a given stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  array $wrestlerIds
     * @param  array $tagTeamIds
     */
    private function updateMembers(Stable $stable, array $wrestlerIds, array $tagTeamIds)
    {
        $now = now()->toDateTimeString();

        if ($stable->currentWrestlers->isEmpty()) {
            $this->stableRepository->addWrestlers($stable, $wrestlerIds, $now);
        } else {
            $currentWrestlerIds = collect($stable->currentWrestlers->modelKeys());
            $suggestedWrestlerIds = collect($wrestlerIds);
            $formerWrestlerIds = $currentWrestlerIds->diff($suggestedWrestlerIds)->toArray();
            $newWrestlerIds = $suggestedWrestlerIds->diff($currentWrestlerIds)->toArray();

            $this->stableRepository->removeWrestlers($stable, $formerWrestlerIds, $now);
            $this->stableRepository->addWrestlers($stable, $newWrestlerIds, $now);
        }

        if ($stable->currentTagTeams->isEmpty()) {
            $this->stableRepository->addTagTeams($stable, $tagTeamIds, $now);
        } else {
            $currentTagTeamIds = collect($stable->currentTagTeams->modelKeys());
            $suggestedTagTeamIds = collect($tagTeamIds);
            $formerTagTeamIds = $currentTagTeamIds->diff($suggestedTagTeamIds)->toArray();
            $newTagTeamIds = $suggestedTagTeamIds->diff($currentTagTeamIds)->toArray();

            $this->stableRepository->removeTagTeams($stable, $formerTagTeamIds, $now);
            $this->stableRepository->addTagTeams($stable, $newTagTeamIds, $now);
        }

        return $stable;
    }

    /**
     * Update the activation start date of a given stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  string $activationDate
     * @return \App\Models\Stable $stable
     */
    public function activateOrUpdateActivation(Stable $stable, string $activationDate)
    {
        if ($stable->isNotInActivation()) {
            return $this->stableRepository->activate($stable, $activationDate);
        }

        if ($stable->hasFutureActivation() && ! $stable->activatedOn($activationDate)) {
            return $this->stableRepository->updateActivation($stable, $activationDate);
        }

        return $stable;
    }

    /**
     * Add given tag teams to a given stable on a given join date.
     *
     * @param  \App\Models\Stable $stable
     * @param  array $tagTeamIds
     * @param  string $joinedDate
     * @return void
     */
    public function addTagTeams($stable, $tagTeamIds, $joinedDate)
    {
        foreach ($tagTeamIds as $tagTeamId) {
            $stable->tagTeams()->attach($tagTeamId, ['joined_at' => $joinedDate]);
        }
    }

    /**
     * Update the wrestlers of the stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  array  $wrestlerIds
     * @return void
     */
    public function updateWrestlers(Stable $stable, $wrestlerIds)
    {
        $now = now()->toDateTimeString();

        if ($stable->currentWrestlers->isEmpty()) {
            $this->stableRepository->addWrestlers($stable, $wrestlerIds, $now);
        } else {
            $currentWrestlerIds = collect($stable->currentWrestlers->modelKeys());
            $suggestedWrestlerIds = collect($wrestlerIds);
            $formerWrestlerIds = $currentWrestlerIds->diff($suggestedWrestlerIds)->toArray();
            $newWrestlerIds = $suggestedWrestlerIds->diff($currentWrestlerIds)->toArray();

            $this->stableRepository->removeCurrentWrestlers($stable, $formerWrestlerIds, $now);
            $this->stableRepository->addWrestlers($stable, $newWrestlerIds, $now);
        }
    }
}
