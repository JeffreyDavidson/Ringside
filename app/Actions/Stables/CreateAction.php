<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Create a new create action instance.
     */
    public function __construct(
        protected EstablishAction $establishAction,
        protected AddStableMembersAction $addStableMembersAction,
    ) {}

    /**
     * Create a stable.
     *
     * This handles the complete stable creation workflow:
     * - Creates the stable record with name and description
     * - Adds wrestlers, tag teams, and managers as founding members
     * - Establishes the stable with official debut if debut_date provided; an end date is rejected,
     *   because a stable that starts already ended would keep its founding members (DisbandAction ends a stable)
     * - Creates proper membership tracking with join dates
     * - Makes the stable available for storylines and championship opportunities
     *
     * @param  StableData  $stableData  The data transfer object containing stable information
     * @param  int|null  $promotionId  The promotion of the stable this one is split from; null leaves it to the promotion context, as for a stable created through the form
     * @return Stable The newly created stable with all members
     */
    public function handle(StableData $stableData, ?int $promotionId = null): Stable
    {
        return DB::transaction(function () use ($stableData, $promotionId): Stable {
            $stable = Stable::query()
                ->make(['name' => $stableData->getTrimmedName()])
                ->forceFill(['promotion_id' => $promotionId]);

            if ($stableData->end_date instanceof Carbon) {
                throw CannotBeEstablishedException::withEndDate($stable);
            }

            $stable->save();

            // Use enhanced DTO methods
            $joinDate = $stableData->getJoinDate();

            $this->addStableMembersAction->handle($stable, $stableData->members, $joinDate);

            // Use enhanced DTO method instead of isset check
            if ($stableData->shouldEstablish()) {
                $this->establishAction->handle(
                    $stable,
                    $stableData->start_date,
                );
            }

            return $stable;
        });
    }
}
