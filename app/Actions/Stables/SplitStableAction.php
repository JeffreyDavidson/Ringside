<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableMembershipData;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Services\StableMembershipService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
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

            // Filter members to only include employed/available ones and create Eloquent Collections
            $availableWrestlers = null;
            $availableTagTeams = null;

            if (isset($membersForNewStable['wrestlers'])) {
                $filteredWrestlers = collect($membersForNewStable['wrestlers'])
                    ->filter(fn ($wrestler) => $wrestler->isEmployed());
                
                if ($filteredWrestlers->isNotEmpty()) {
                    $availableWrestlers = new Collection($filteredWrestlers->all());
                }
            }

            if (isset($membersForNewStable['tagTeams'])) {
                $filteredTagTeams = collect($membersForNewStable['tagTeams'])
                    ->filter(fn ($tagTeam) => $tagTeam->isEmployed());
                
                if ($filteredTagTeams->isNotEmpty()) {
                    $availableTagTeams = new Collection($filteredTagTeams->all());
                }
            }

            // Use service to transfer available members
            if ($availableWrestlers !== null || $availableTagTeams !== null) {
                $membershipData = new StableMembershipData(
                    wrestlers: $availableWrestlers,
                    tagTeams: $availableTagTeams
                );

                $membershipService = app(StableMembershipService::class);
                $membershipService->transferMembers($originalStable, $newStable, $membershipData, $date);
            }

            return $newStable;
        });
    }
}
