<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use App\Services\StableValidationService;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction
{
    use AsAction;

    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected EstablishAction $establishAction
    ) {}

    /**
     * Update a stable.
     *
     * This handles the complete stable update workflow:
     * - Updates stable information (name, description)
     * - Handles establishment date changes if allowed
     * - Updates stable membership (wrestlers, tag teams, managers)
     * - Maintains stable integrity and member relationships
     *
     * @param  Stable  $stable  The stable to update
     * @param  StableData  $stableData  The updated stable information
     * @return Stable The updated stable instance
     *
     * @example
     * ```php
     * $stableData = new StableData(
     *     name: 'Updated Stable Name',
     *     start_date: null,
     *     members: new StableMembershipData(
     *         wrestlers: collect([$wrestler1, $wrestler2, $wrestler3]),
     *         tagTeams: collect([])
     *     )
     * );
     * $updatedStable = UpdateAction::run($stable, $stableData);
     * ```
     */
    public function handle(Stable $stable, StableData $stableData): Stable
    {
        return DB::transaction(function () use ($stable, $stableData): Stable {
            // Validate business rules before updating
            $validationService = app(StableValidationService::class);
            $validationService->validateUniqueName($stableData->getTrimmedName(), $stable);
            $validationService->validateMembersAvailable($stableData->members);

            $stable->update([
                'name' => $stableData->getTrimmedName(),
            ]);

            // Use enhanced DTO method and centralized validation
            if ($stableData->hasStartDate()) {
                $validationService->validateEstablishmentDateChange($stable);
                $this->establishAction->handle($stable, $stableData->start_date);
            }

            // Update stable membership using service
            $membershipService = app(StableMembershipService::class);
            $membershipService->updateMembership($stable, $stableData->members, now());

            return $stable;
        });
    }
}
