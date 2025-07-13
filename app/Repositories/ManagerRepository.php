<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Managers\ManagerData;
use App\Models\Managers\Manager;
use App\Models\Managers\ManagerEmployment;
use App\Models\Managers\ManagerInjury;
use App\Models\Managers\ManagerRetirement;
use App\Models\Managers\ManagerSuspension;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamManager;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\Concerns\ManagesEmployment;
use App\Repositories\Concerns\ManagesInjury;
use App\Repositories\Concerns\ManagesMembers;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Concerns\ManagesSuspension;
use App\Repositories\Contracts\ManagerRepositoryInterface;
use App\Repositories\Contracts\ManagesEmployment as ManagesEmploymentContract;
use App\Repositories\Contracts\ManagesInjury as ManagesInjuryContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\ManagesSuspension as ManagesSuspensionContract;
use App\Repositories\Support\BaseRepository;
use Illuminate\Support\Carbon;
use Tests\Unit\Repositories\ManagerRepositoryTest;

/**
 * Repository for Manager model business operations and data persistence.
 *
 * Handles all manager related database operations including CRUD operations,
 * employment/retirement/suspension/injury management, and wrestler/tag team relationships.
 *
 * @see ManagerRepositoryTest
 */
class ManagerRepository extends BaseRepository implements ManagerRepositoryInterface, ManagesEmploymentContract, ManagesInjuryContract, ManagesRetirementContract, ManagesSuspensionContract
{
    /** @use ManagesEmployment<ManagerEmployment, Manager> */
    use ManagesEmployment;

    /** @use ManagesInjury<ManagerInjury, Manager> */
    use ManagesInjury;

    /** @use ManagesMembers<TagTeamManager, Manager> */
    use ManagesMembers;

    /** @use ManagesRetirement<ManagerRetirement, Manager> */
    use ManagesRetirement;

    /** @use ManagesSuspension<ManagerSuspension, Manager> */
    use ManagesSuspension;

    /**
     * Create a new manager.
     */
    public function create(ManagerData $managerData): Manager
    {
        /** @var Manager $manager */
        $manager = Manager::query()->create([
            'first_name' => $managerData->first_name,
            'last_name' => $managerData->last_name,
        ]);

        return $manager;
    }

    /**
     * Update a manager.
     */
    public function update(Manager $manager, ManagerData $managerData): Manager
    {
        $manager->update([
            'first_name' => $managerData->first_name,
            'last_name' => $managerData->last_name,
        ]);

        return $manager;
    }

    /**
     * Remove manager from all current tag teams.
     */
    public function removeFromCurrentTagTeams(Manager $manager, Carbon $removalDate): void
    {
        $manager->currentTagTeams()->get()->each(function (TagTeam $tagTeam) use ($manager, $removalDate): void {
            // Use specific manager column names (fired_at instead of left_at)
            $manager->currentTagTeams()
                ->wherePivotNull('fired_at')
                ->updateExistingPivot($tagTeam->getKey(), ['fired_at' => $removalDate->toDateTimeString()]);
        });
    }

    /**
     * Remove manager from all current wrestlers.
     */
    public function removeFromCurrentWrestlers(Manager $manager, Carbon $removalDate): void
    {
        $manager->currentWrestlers->each(function (Wrestler $wrestler) use ($manager, $removalDate): void {
            // Use specific manager column names (fired_at instead of left_at)
            $manager->currentWrestlers()
                ->wherePivotNull('fired_at')
                ->updateExistingPivot($wrestler->getKey(), ['fired_at' => $removalDate->toDateTimeString()]);
        });
    }

    /**
     * Restore a soft-deleted manager.
     */
    public function restore(Manager $manager): void
    {
        $manager->restore();
    }
}
