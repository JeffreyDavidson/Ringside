<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\StableData;
use App\Models\Manager;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class StableRepository
{
    /**
     * Create a new stable with the given data.
     */
    public function create(StableData $stableData): Stable
    {
        return Stable::query()->create([
            'name' => $stableData->name,
        ]);
    }

    /**
     * Update the given stable with the given data.
     */
    public function update(Stable $stable, StableData $stableData): Stable
    {
        $stable->update([
            'name' => $stableData->name,
        ]);

        return $stable;
    }

    /**
     * Delete a given stable.
     */
    public function delete(Stable $stable): void
    {
        $stable->delete();
    }

    /**
     * Restore a given stable.
     */
    public function restore(Stable $stable): void
    {
        $stable->restore();
    }

    /**
     * Activate a given stable on a given date.
     */
    public function activate(Stable $stable, Carbon $activationDate): Stable
    {
        $stable->activations()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $activationDate->toDateTimeString()]
        );

        return $stable;
    }

    /**
     * Deactivate a given stable on a given date.
     */
    public function deactivate(Stable $stable, Carbon $deactivationDate): Stable
    {
        $stable->currentActivation()->update(['ended_at' => $deactivationDate->toDateTimeString()]);

        return $stable;
    }

    /**
     * Retire a given stable on a given date.
     */
    public function retire(Stable $stable, Carbon $retirementDate): Stable
    {
        $stable->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);

        return $stable;
    }

    /**
     * Unretire a given stable on a given date.
     */
    public function unretire(Stable $stable, Carbon $unretireDate): Stable
    {
        $stable->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $stable;
    }

    /**
     * Unretire a given stable on a given date.
     */
    public function disassemble(Stable $stable, Carbon $disassembleDate): Stable
    {
        $stable->currentWrestlers()->each(
            fn (Wrestler $wrestler) => $stable->currentWrestlers()->updateExistingPivot(
                $wrestler->id,
                ['left_at' => $disassembleDate->toDateTimeString()]
            )
        );

        $stable->currentTagTeams()->each(
            fn (TagTeam $tagTeam) => $stable->currentTagTeams()->updateExistingPivot(
                $tagTeam->id,
                ['left_at' => $disassembleDate->toDateTimeString()]
            )
        );

        return $stable;
    }

    /**
     * Add wrestlers to a given stable.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function addWrestlers(Stable $stable, Collection $wrestlers, Carbon $joinDate): void
    {
        $wrestlers->each(function (Wrestler $wrestler) use ($stable, $joinDate): void {
            $stable->currentWrestlers()->attach($wrestler->id, ['joined_at' => $joinDate->toDateTimeString()]);
        });
    }

    /**
     * Add tag teams to a given stable at a given date.
     *
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function addTagTeams(Stable $stable, Collection $tagTeams, Carbon $joinDate): void
    {
        $tagTeams->each(function (TagTeam $tagTeam) use ($stable, $joinDate): void {
            $stable->currentTagTeams()->attach($tagTeam->id, ['joined_at' => $joinDate->toDateTimeString()]);
        });
    }

    /**
     * Add managers to a given stable.
     *
     * @param  Collection<int, Manager>  $managers
     */
    public function addManagers(Stable $stable, Collection $managers, Carbon $joinDate): void
    {
        $managers->each(function (Manager $manager) use ($stable, $joinDate): void {
            $stable->currentManagers()->attach($manager->id, ['joined_at' => $joinDate->toDateTimeString()]);
        });
    }

    /**
     * Undocumented function.
     *
     * @param  Collection<int, Wrestler>  $currentWrestlers
     */
    public function removeWrestlers(Stable $stable, Collection $currentWrestlers, Carbon $removalDate): void
    {
        $currentWrestlers->each(function (Wrestler $wrestler) use ($stable, $removalDate): void {
            $stable->currentWrestlers()->updateExistingPivot(
                $wrestler->id,
                ['left_at' => $removalDate->toDateTimeString()]
            );
        });
    }

    /**
     * Undocumented function.
     *
     * @param  Collection<int, TagTeam>  $currentTagTeams
     */
    public function removeTagTeams(Stable $stable, Collection $currentTagTeams, Carbon $removalDate): void
    {
        $currentTagTeams->each(function (TagTeam $tagTeam) use ($stable, $removalDate): void {
            $stable->currentTagTeams()->updateExistingPivot(
                $tagTeam->id,
                ['left_at' => $removalDate->toDateTimeString()]
            );
        });
    }

    /**
     * Undocumented function.
     *
     * @param  Collection<int, Manager>  $currentManagers
     */
    public function removeManagers(Stable $stable, Collection $currentManagers, Carbon $removalDate): void
    {
        $currentManagers->each(function (Manager $manager) use ($stable, $removalDate): void {
            $stable->currentManagers()->updateExistingPivot(
                $manager->id,
                ['left_at' => $removalDate->toDateTimeString()]
            );
        });
    }
}
