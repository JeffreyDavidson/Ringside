<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use App\Services\StableValidationService;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Create a new create action instance.
     */
    public function __construct(
        protected EstablishAction $establishAction
    ) {}

    /**
     * Create a stable.
     *
     * This handles the complete stable creation workflow:
     * - Creates the stable record with name and description
     * - Adds wrestlers, tag teams, and managers as founding members
     * - Establishes the stable with official debut if debut_date provided
     * - Creates proper membership tracking with join dates
     * - Makes the stable available for storylines and championship opportunities
     *
     * @param  StableData  $stableData  The data transfer object containing stable information
     * @return Stable The newly created stable with all members
     */
    public function handle(StableData $stableData): Stable
    {
        return DB::transaction(function () use ($stableData): Stable {
            // Validate business rules before creation
            $validationService = app(StableValidationService::class);
            $validationService->validateUniqueName($stableData->getTrimmedName());
            $validationService->validateMembersAvailable($stableData->members);

            $stable = Stable::create([
                'name' => $stableData->getTrimmedName(),
            ]);

            // Use enhanced DTO methods
            $joinDate = $stableData->getJoinDate();

            // Add members using service
            $membershipService = app(StableMembershipService::class);
            $membershipService->addMembers($stable, $stableData->members, $joinDate);

            // Use enhanced DTO method instead of isset check
            if ($stableData->shouldEstablish()) {
                $this->establishAction->handle($stable, $stableData->start_date);
            }

            return $stable;
        });
    }
}
