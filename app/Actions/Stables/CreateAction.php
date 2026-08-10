<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Create a new create action instance.
     */
    public function __construct(
        protected EstablishAction $establishAction,
        protected StableMembershipService $membershipService,
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
            $stable = Stable::query()->create([
                'name' => $stableData->getTrimmedName(),
            ]);

            // Use enhanced DTO methods
            $joinDate = $stableData->getJoinDate();

            // Add members using service
            $this->membershipService->addMembers($stable, $stableData->members, $joinDate);

            // Use enhanced DTO method instead of isset check
            if ($stableData->shouldEstablish()) {
                $this->establishAction->handle($stable, $stableData->start_date);

                if ($stableData->end_date) {
                    $stable->currentActivityPeriod()->update([
                        'ended_at' => $stableData->end_date,
                    ]);
                }
            }

            return $stable;
        });
    }
}
