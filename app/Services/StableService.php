<?php

namespace App\Services;

use App\Models\Stable;

class StableService
{
    /**
     * Creates a new stable.
     *
     * @param  array $data
     * @return \App\Models\Stable
     */
    public function create(array $data): Stable
    {
        $stable = Stable::create(['name' => $data['name']]);

        if ($data['started_at']) {
            $stable->activations()->create(['started_at' => $data['started_at']]);
        }

        $this->addMembers($stable, $data['wrestlers'], $data['tag_teams']);

        return $stable;
    }

    /**
     * Updates a stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  array $data
     * @return \App\Models\Stable
     */
    public function update(Stable $stable, array $data): Stable
    {
        $stable->update(['name' => $data['name']]);

        $this->updateActivation($stable, $data['started_at']);

        $this->updateMembers($stable, $data['wrestlers'], $data['tag_teams']);

        return $stable;
    }

    /**
     * Add members to a stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  array $wrestlerIds
     * @param  array $tagTeamIds
     * @param  string $joinedDate
     * @return \App\Models\Stable
     */
    public function addMembers(Stable $stable, array $wrestlerIds, array $tagTeamIds, $joinedDate = null): Stable
    {
        $joinedDate = $joinedDate ?? now();

        if ($wrestlerIds) {
            $stable->addWrestlers($wrestlerIds, $joinedDate);
        }

        if ($tagTeamIds) {
            $stable->addTagTeams($tagTeamIds, $joinedDate);
        }

        return $stable;
    }

    /**
     * Update the members of a stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  array $wrestlerIds
     * @param  array $tagTeamIds
     * @return \App\Models\Stable
     */
    public function updateMembers(Stable $stable, array $wrestlerIds, array $tagTeamIds): Stable
    {
        if ($stable->currentWrestlers->isEmpty()) {
            if ($wrestlerIds) {
                foreach ($wrestlerIds as $wrestlerId) {
                    $stable->currentWrestlers()->attach($wrestlerId, ['joined_at' => now()]);
                }
            }
        } else {
            $currentWrestlerIds = collect($stable->currentWrestlers->modelKeys());
            $suggestedWrestlerIds = collect($wrestlerIds);
            $formerWrestlerIds = $currentWrestlerIds->diff($suggestedWrestlerIds);
            $newWrestlerIds = $suggestedWrestlerIds->diff($currentWrestlerIds);

            $now = now();

            foreach ($formerWrestlerIds as $formerWrestlerId) {
                $stable->currentWrestlers()->updateExistingPivot($formerWrestlerId, ['left_at' => $now]);
            }

            foreach ($newWrestlerIds as $newWrestlerId) {
                $stable->currentWrestlers()->attach($newWrestlerId, ['joined_at' => $now]);
            }
        }

        if ($stable->currentTagTeams->isEmpty()) {
            if ($tagTeamIds) {
                foreach ($tagTeamIds as $tagTeamId) {
                    $stable->currentTagTeams()->attach($tagTeamId, ['joined_at' => now()]);
                }
            }
        } else {
            $currentTagTeamIds = collect($stable->currentTagTeams->modelKeys());
            $suggestedTagTeamIds = collect($tagTeamIds);
            $formerTagTeamIds = $currentTagTeamIds->diff($suggestedTagTeamIds);
            $newTagTeamIds = $suggestedTagTeamIds->diff($currentTagTeamIds);

            $now = now();

            foreach ($formerTagTeamIds as $formerTagTeamId) {
                $stable->currentTagTeams()->updateExistingPivot($formerTagTeamId, ['left_at' => $now]);
            }

            foreach ($newTagTeamIds as $newTagTeamId) {
                $stable->currentTagTeams()->attach($newTagTeamId, ['joined_at' => $now]);
            }
        }

        return $stable;
    }

    /**
     * Update the activation start date for a stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  string $startDate
     * @return \App\Models\Stable
     */
    public function updateActivation(Stable $stable, string $startDate): Stable
    {
        if ($startDate) {
            if ($stable->currentEmployment && $stable->currentEmployment->started_at != $startDate) {
                $stable->currentActivation()->update(['started_at' => $startDate]);
            } elseif (! $stable->currentEmployment) {
                $stable->activations()->create(['started_at' => $startDate]);
            }
        }

        return $stable;
    }
}
