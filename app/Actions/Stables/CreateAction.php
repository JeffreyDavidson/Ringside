<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction
{
    use AsAction;

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
     *
     * @example
     * ```php
     * // Create stable with immediate debut
     * $stableData = new StableData(
     *     name: 'The Four Horsemen',
     *     start_date: now(),
     *     members: new StableMembershipData(
     *         wrestlers: collect([$ricFlair, $arnAnderson, $tullyblanchard]),
     *         tagTeams: collect([])
     *     )
     * );
     * $stable = CreateAction::run($stableData);
     *
     * // Create stable without debut (must be debuted separately)
     * $stableData = new StableData(
     *     name: 'D-Generation X',
     *     start_date: null,
     *     members: new StableMembershipData(
     *         wrestlers: collect([$shawnMichaels, $tripleH]),
     *         tagTeams: collect([])
     *     )
     * );
     * $stable = CreateAction::run($stableData);
     * ```
     */
    public function handle(StableData $stableData): Stable
    {
        return DB::transaction(function () use ($stableData): Stable {
            $stable = Stable::create([
                'name' => $stableData->name,
            ]);

            $joinDate = $stableData->start_date ?? now();

            // Add members using service
            $membershipService = app(StableMembershipService::class);
            $membershipService->addMembers($stable, $stableData->members, $joinDate);

            if (isset($stableData->start_date)) {
                $stable->activityPeriods()->create([
                    'started_at' => $stableData->start_date,
                    'ended_at' => null,
                ]);
            }

            return $stable;
        });
    }
}
