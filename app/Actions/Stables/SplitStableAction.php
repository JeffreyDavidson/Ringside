<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Collection;
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
        // Validate that the original stable can be split
        if (method_exists($originalStable, 'isRetired') && $originalStable->isRetired()) {
            throw new \Exception('Cannot split a retired stable');
        }
        
        if (method_exists($originalStable, 'isCurrentlyActive') && !$originalStable->isCurrentlyActive()) {
            throw new \Exception('Cannot split an inactive stable');
        }
        
        // Validate that the new stable name is unique
        if (Stable::where('name', $newStableName)->exists()) {
            throw new \Exception('A stable with this name already exists');
        }

        return DB::transaction(function () use ($originalStable, $newStableName, $membersForNewStable, $date): Stable {
            // Create the new stable
            $newStable = $this->stableRepository->create(
                new StableData(
                    name: $newStableName,
                    start_date: null,
                    tagTeams: new Collection(),
                    wrestlers: new Collection(),
                    managers: new Collection()
                )
            );

            // Create activity period to make the stable active
            $this->stableRepository->createDebut($newStable, $date);

            // Transfer wrestlers (only if they are employed/available)
            if (isset($membersForNewStable['wrestlers'])) {
                foreach ($membersForNewStable['wrestlers'] as $wrestler) {
                    // Only transfer wrestlers who are employed/available
                    if (method_exists($wrestler, 'isEmployed') && $wrestler->isEmployed()) {
                        $this->stableRepository->removeWrestler($originalStable, $wrestler, $date);
                        $this->stableRepository->addWrestler($newStable, $wrestler, $date);
                    }
                    // Skip unemployed wrestlers without throwing an exception
                }
            }

            // Transfer tag teams (only if they are employed/available)
            if (isset($membersForNewStable['tagTeams'])) {
                foreach ($membersForNewStable['tagTeams'] as $tagTeam) {
                    // Only transfer tag teams who are employed/available
                    if (method_exists($tagTeam, 'isEmployed') && $tagTeam->isEmployed()) {
                        $this->stableRepository->removeTagTeam($originalStable, $tagTeam, $date);
                        $this->stableRepository->addTagTeam($newStable, $tagTeam, $date);
                    }
                    // Skip unemployed tag teams without throwing an exception
                }
            }

            // Note: Managers are not directly associated with stables
            // They are managed through their wrestlers/tag teams

            return $newStable;
        });
    }
}
