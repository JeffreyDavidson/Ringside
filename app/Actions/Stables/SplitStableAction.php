<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Services\StableMembershipService;
use App\Services\StableValidationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class SplitStableAction
{
    use AsAction;

    /**
     * Create a new split stable action instance.
     */
    public function __construct(
        protected CreateAction $createAction
    ) {}

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
        return DB::transaction(function () use ($originalStable, $newStableName, $membersForNewStable, $date): Stable {
            // Validate all business rules using centralized validation service
            $validationService = app(StableValidationService::class);
            $validationService->validateCanSplit($originalStable);
            $validationService->validateUniqueName(mb_trim($newStableName));
            $validationService->validateSplitMembers($originalStable, $membersForNewStable);

            // Convert array to StableMembershipData for filtering
            $wrestlers = isset($membersForNewStable['wrestlers'])
                ? new Collection($membersForNewStable['wrestlers'])
                : null;
            $tagTeams = isset($membersForNewStable['tagTeams'])
                ? new Collection($membersForNewStable['tagTeams'])
                : null;

            $membershipData = new StableMembershipData(
                wrestlers: $wrestlers,
                tagTeams: $tagTeams
            );

            // Use enhanced DTO method to filter employed members
            $employedMembers = $membershipData->filterEmployedMembers();

            // Validate the filtered members are still viable
            if ($employedMembers->isEmpty()) {
                throw new InvalidArgumentException('Cannot split stable: no employed members available for new stable.');
            }

            // Create StableData for the new stable
            $stableData = new StableData(
                name: mb_trim($newStableName),
                start_date: $date,
                members: $employedMembers
            );

            // Use injected CreateAction to create and establish the new stable with members
            $newStable = $this->createAction->handle($stableData);

            // Remove transferred members from original stable using service
            $membershipService = app(StableMembershipService::class);
            $membershipService->removeMembers($originalStable, $employedMembers, $date);

            return $newStable;
        });
    }
}
