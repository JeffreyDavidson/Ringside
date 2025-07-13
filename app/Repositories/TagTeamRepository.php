<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\TagTeams\TagTeamData;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamEmployment;
use App\Models\TagTeams\TagTeamRetirement;
use App\Models\TagTeams\TagTeamSuspension;
use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\Concerns\ManagesEmployment;
use App\Repositories\Concerns\ManagesMembers;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Concerns\ManagesSuspension;
use App\Repositories\Contracts\ManagesEmployment as ManagesEmploymentContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\ManagesSuspension as ManagesSuspensionContract;
use App\Repositories\Contracts\ManagesTagTeamMembers;
use App\Repositories\Contracts\TagTeamRepositoryInterface;
use App\Repositories\Support\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Tests\Unit\Repositories\TagTeamRepositoryTest;

/**
 * Repository for TagTeam model business operations and data persistence.
 *
 * Handles all tag team related database operations including CRUD operations,
 * employment/retirement/suspension management, and wrestler/manager relationships.
 *
 * @see TagTeamRepositoryTest
 */
class TagTeamRepository extends BaseRepository implements ManagesEmploymentContract, ManagesRetirementContract, ManagesSuspensionContract, ManagesTagTeamMembers, TagTeamRepositoryInterface
{
    /** @use ManagesEmployment<TagTeamEmployment, TagTeam> */
    use ManagesEmployment;

    /** @use ManagesMembers<TagTeamWrestler, TagTeam> */
    use ManagesMembers;

    /** @use ManagesRetirement<TagTeamRetirement, TagTeam> */
    use ManagesRetirement;

    /** @use ManagesSuspension<TagTeamSuspension, TagTeam> */
    use ManagesSuspension;

    /**
     * Create a new tag team.
     */
    public function create(TagTeamData $tagTeamData): TagTeam
    {
        return TagTeam::query()->create([
            'name' => $tagTeamData->name,
            'signature_move' => $tagTeamData->signature_move,
        ]);
    }

    /**
     * Update a tag team.
     */
    public function update(TagTeam $tagTeam, TagTeamData $tagTeamData): TagTeam
    {
        $tagTeam->update([
            'name' => $tagTeamData->name,
            'signature_move' => $tagTeamData->signature_move,
        ]);

        return $tagTeam;
    }

    /**
     * Add a wrestler to the tag team.
     */
    public function addWrestler(TagTeam $tagTeam, Wrestler $wrestler, Carbon $joinDate): void
    {
        $this->addMember($tagTeam, 'wrestlers', $wrestler, $joinDate);
    }

    /**
     * Remove a wrestler from the tag team.
     */
    public function removeWrestler(TagTeam $tagTeam, Wrestler $wrestler, Carbon $removalDate): void
    {
        $this->removeCurrentMember($tagTeam, 'wrestlers', $wrestler, $removalDate);
    }

    /**
     * Add multiple wrestlers to the tag team.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function addWrestlers(TagTeam $tagTeam, Collection $wrestlers, Carbon $joinDate): void
    {
        $wrestlers->each(function (Wrestler $wrestler) use ($tagTeam, $joinDate): void {
            $this->addWrestler($tagTeam, $wrestler, $joinDate);
        });
    }

    /**
     * Remove multiple wrestlers from the tag team.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function removeWrestlers(TagTeam $tagTeam, Collection $wrestlers, Carbon $removalDate): void
    {
        $wrestlers->each(function (Wrestler $wrestler) use ($tagTeam, $removalDate): void {
            $this->removeWrestler($tagTeam, $wrestler, $removalDate);
        });
    }

    /**
     * Sync tag team wrestlers (remove old, add new).
     *
     * @param  Collection<int, Wrestler>  $formerPartners
     * @param  Collection<int, Wrestler>  $newPartners
     */
    public function syncWrestlers(
        TagTeam $tagTeam,
        Collection $formerPartners,
        Collection $newPartners,
        Carbon $date
    ): void {
        $formerPartners->each(function (Wrestler $wrestler) use ($tagTeam, $date): void {
            $this->removeWrestler($tagTeam, $wrestler, $date);
        });

        $newPartners->each(function (Wrestler $wrestler) use ($tagTeam, $date): void {
            $this->addWrestler($tagTeam, $wrestler, $date);
        });
    }

    /**
     * Update tag team partners with given wrestlers.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function updateTagTeamPartners(TagTeam $tagTeam, Collection $wrestlers, ?Carbon $joinDate = null): void
    {
        $joinDate ??= now();

        if ($tagTeam->currentWrestlers->isEmpty()) {
            if ($wrestlers->isNotEmpty()) {
                $this->addWrestlers($tagTeam, $wrestlers, $joinDate);
            }
        } else {
            /** @var Collection<int, Wrestler> $formerTagTeamPartners */
            $formerTagTeamPartners = $tagTeam->currentWrestlers()->wherePivotNotIn(
                'wrestler_id',
                $wrestlers->modelKeys()
            )->get();

            $newTagTeamPartners = $wrestlers->except($formerTagTeamPartners->modelKeys());

            $this->syncWrestlers($tagTeam, $formerTagTeamPartners, $newTagTeamPartners, $joinDate);
        }
    }

    /**
     * Add a manager to the tag team.
     */
    public function addManager(TagTeam $tagTeam, Manager $manager, Carbon $hireDate): void
    {
        $tagTeam->managers()->attach($manager->getKey(), [
            'hired_at' => $hireDate->toDateTimeString(),
        ]);
    }

    /**
     * Remove a manager from the tag team.
     */
    public function removeManager(TagTeam $tagTeam, Manager $manager, Carbon $leaveDate): void
    {
        $tagTeam->managers()->wherePivotNull('fired_at')->updateExistingPivot($manager->getKey(), [
            'fired_at' => $leaveDate->toDateTimeString(),
        ]);
    }

    /**
     * Add multiple managers to the tag team.
     *
     * @param  Collection<int, Manager>  $managers
     */
    public function addManagers(TagTeam $tagTeam, Collection $managers, Carbon $hireDate): void
    {
        $managers->each(function (Manager $manager) use ($tagTeam, $hireDate): void {
            $this->addManager($tagTeam, $manager, $hireDate);
        });
    }

    /**
     * Remove multiple managers from the tag team.
     *
     * @param  Collection<int, Manager>  $managers
     */
    public function removeManagers(TagTeam $tagTeam, Collection $managers, Carbon $leaveDate): void
    {
        $managers->each(function (Manager $manager) use ($tagTeam, $leaveDate): void {
            $this->removeManager($tagTeam, $manager, $leaveDate);
        });
    }

    /**
     * Restore a soft-deleted tag team.
     */
    public function restore(TagTeam $tagTeam): void
    {
        $tagTeam->restore();
    }
}
