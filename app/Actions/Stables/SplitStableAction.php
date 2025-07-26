<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SplitStableAction
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
     * @param  array{wrestlers?: array<int, Wrestler>, tagTeams?: array<int, TagTeam>}  $membersForNewStable  Array of members to move to new stable, grouped by type
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
        if ($originalStable->isRetired()) {
            throw new Exception('Cannot split a retired stable');
        }

        if (! $originalStable->isCurrentlyActive()) {
            throw new Exception('Cannot split an inactive stable');
        }

        // Validate that the new stable name is unique
        if (Stable::where('name', $newStableName)->exists()) {
            throw new Exception('A stable with this name already exists');
        }

        return DB::transaction(function () use ($originalStable, $newStableName, $membersForNewStable, $date): Stable {
            // Create the new stable
            $newStable = Stable::create([
                'name' => $newStableName,
            ]);

            // Create activity period to make the stable active
            $newStable->activityPeriods()->create([
                'started_at' => $date,
                'ended_at' => null,
            ]);

            // Transfer wrestlers (only if they are employed/available)
            if (isset($membersForNewStable['wrestlers'])) {
                foreach ($membersForNewStable['wrestlers'] as $wrestler) {
                    // Only transfer wrestlers who are employed/available
                    if (method_exists($wrestler, 'isEmployed') && $wrestler->isEmployed()) {
                        // Remove from original stable
                        $originalStable->wrestlers()->updateExistingPivot($wrestler->id, [
                            'left_at' => $date,
                        ]);
                        // Add to new stable
                        $newStable->wrestlers()->attach($wrestler->id, [
                            'joined_at' => $date,
                            'left_at' => null,
                        ]);
                    }
                    // Skip unemployed wrestlers without throwing an exception
                }
            }

            // Transfer tag teams (only if they are employed/available)
            if (isset($membersForNewStable['tagTeams'])) {
                foreach ($membersForNewStable['tagTeams'] as $tagTeam) {
                    // Only transfer tag teams who are employed/available
                    if (method_exists($tagTeam, 'isEmployed') && $tagTeam->isEmployed()) {
                        // Remove from original stable
                        $originalStable->tagTeams()->updateExistingPivot($tagTeam->id, [
                            'left_at' => $date,
                        ]);
                        // Add to new stable
                        $newStable->tagTeams()->attach($tagTeam->id, [
                            'joined_at' => $date,
                            'left_at' => null,
                        ]);
                    }
                    // Skip unemployed tag teams without throwing an exception
                }
            }

            return $newStable;
        });
    }
}
