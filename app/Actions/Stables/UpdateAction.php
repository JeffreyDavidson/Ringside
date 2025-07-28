<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction
{
    use AsAction;

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
            $stable->update([
                'name' => $stableData->name,
            ]);

            if (isset($stableData->start_date)) {
                $this->validateEstablishmentDateChange($stable);
                $stable->activityPeriods()->create([
                    'started_at' => $stableData->start_date,
                    'ended_at' => null,
                ]);
            }

            // Update stable membership using service
            $membershipService = app(StableMembershipService::class);
            $membershipService->updateMembership($stable, $stableData->members, now());

            return $stable;
        });
    }

    /**
     * Validate that the stable's establishment date can be changed.
     *
     * @throws InvalidArgumentException When establishment date change is not allowed
     */
    private function validateEstablishmentDateChange(Stable $stable): void
    {
        if ($stable->isCurrentlyActive() && ! $stable->hasFutureActivity()) {
            throw new InvalidArgumentException("Establishment date cannot be changed for stable '{$stable->name}' that is currently active.");
        }
    }
}
