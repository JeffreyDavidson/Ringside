<?php

namespace App\Services;

use App\Exceptions\CannotBeDisassembledException;
use App\Models\Stable;
use App\Repositories\StableRepository;

class StableService
{
    protected $stableRepository;

    public function __construct(StableRepository $stableRepository)
    {
        $this->stableRepository = $stableRepository;
    }

    /**
     * Creates a new stable.
     *
     * @param  array $data
     * @return \App\Models\Stable
     */
    public function create(array $data): Stable
    {
        $stable = $this->stableRepository->create($data);

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

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Stable $stable
     * @param  string|null $deactivatedAt
     * @return void
     */
    public function disassemble($stable, $deactivatedAt = null)
    {
        throw_unless($stable->canBeDisassembled(), new CannotBeDisassembledException);

        $deactivationDate = $deactivatedAt ?: now();

        $stable->currentActivation()->update(['ended_at' => $deactivationDate]);
        $stable->currentWrestlers()->detach();
        $stable->currentTagTeams()->detach();
        $stable->updateStatusAndSave();
    }

    /**
     * Determine if the tag team can be disassembled.
     *
     * @return bool
     */
    public function canBeDisassembled()
    {
        if ($this->isNotInActivation()) {
            // throw new CannotBeRetiredException('Stable cannot be retired. This Stable does not have an active activation.');
            return false;
        }

        return true;
    }

    public function addWrestlers($stable, $wrestlerIds, $joinedDate)
    {
        foreach ($wrestlerIds as $wrestlerId) {
            $stable->wrestlers()->attach($wrestlerId, ['joined_at' => $joinedDate]);
        }
    }

    public function addTagTeams($stable, $tagTeamIds, $joinedDate)
    {
        foreach ($tagTeamIds as $tagTeamId) {
            $stable->tagTeams()->attach($tagTeamId, ['joined_at' => $joinedDate]);
        }
    }
}
