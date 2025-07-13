<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseStableAction
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
     * $stableData = new StableData([
     *     'name' => 'The Four Horsemen',
     *     'wrestlers' => [$ricFlair, $arnAnderson, $tullyblanchard],
     *     'managers' => [$jjDillon],
     *     'debut_date' => now()
     * ]);
     * $stable = CreateAction::run($stableData);
     *
     * // Create stable without debut (must be debuted separately)
     * $stableData = new StableData([
     *     'name' => 'D-Generation X',
     *     'wrestlers' => [$shawnMichaels, $tripleH],
     *     'managers' => []
     * ]);
     * $stable = CreateAction::run($stableData);
     * ```
     */
    public function handle(StableData $stableData): Stable
    {
        return DB::transaction(function () use ($stableData): Stable {
            $stable = $this->stableRepository->create($stableData);

            $joinDate = $stableData->start_date ?? now();

            $this->stableRepository->addWrestlers($stable, $stableData->wrestlers, $joinDate);
            $this->stableRepository->addTagTeams($stable, $stableData->tagTeams, $joinDate);
            $this->stableRepository->addManagers($stable, $stableData->managers, $joinDate);

            if (isset($stableData->start_date)) {
                $this->stableRepository->createActivity($stable, $stableData->start_date);
            }

            return $stable;
        });
    }
}
