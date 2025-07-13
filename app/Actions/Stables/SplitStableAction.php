<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SplitStableAction extends BaseStableAction
{
    use AsAction;

    /**
     * Split a stable into two based on member selection.
     *
     * Creates a new stable and transfers specified members from the original
     * stable to the new stable, leaving the remaining members in the original.
     *
     * @param  Stable  $originalStable  The stable to split
     * @param  string  $newStableName  Name for the new stable
     * @param  array<string, mixed>  $membersForNewStable  Array of members to move to new stable, grouped by type
     * @param  Carbon  $date  The date when the split operation occurs
     * @return Stable The newly created stable
     */
    public function handle(
        Stable $originalStable,
        string $newStableName,
        array $membersForNewStable,
        Carbon $date
    ): Stable {
        return DB::transaction(function () use ($originalStable, $newStableName, $membersForNewStable, $date): Stable {
            // Create the new stable
            $newStable = $this->stableRepository->create(
                new StableData(
                    name: $newStableName,
                    start_date: null,
                    tagTeams: collect(),
                    wrestlers: collect(),
                    managers: collect()
                )
            );

            // Transfer wrestlers
            if (isset($membersForNewStable['wrestlers'])) {
                foreach ($membersForNewStable['wrestlers'] as $wrestler) {
                    $this->stableRepository->removeWrestler($originalStable, $wrestler, $date);
                    $this->stableRepository->addWrestler($newStable, $wrestler, $date);
                }
            }

            // Transfer tag teams
            if (isset($membersForNewStable['tagTeams'])) {
                foreach ($membersForNewStable['tagTeams'] as $tagTeam) {
                    $this->stableRepository->removeTagTeam($originalStable, $tagTeam, $date);
                    $this->stableRepository->addTagTeam($newStable, $tagTeam, $date);
                }
            }

            // Transfer managers
            if (isset($membersForNewStable['managers'])) {
                foreach ($membersForNewStable['managers'] as $manager) {
                    $this->stableRepository->removeManager($originalStable, $manager, $date);
                    $this->stableRepository->addManager($newStable, $manager, $date);
                }
            }

            return $newStable;
        });
    }
}
