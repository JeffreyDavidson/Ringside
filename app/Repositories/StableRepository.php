<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Stables\StableData;
use App\Enums\Stables\StableStatus;
use App\Models\Managers\Manager;
use App\Models\Stables\Stable;
use App\Models\Stables\StableActivityPeriod;
use App\Models\Stables\StableMember;
use App\Models\Stables\StableRetirement;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Models\Contracts\HasActivityPeriods;
use App\Repositories\Concerns\ManagesActivity;
use App\Repositories\Concerns\ManagesMembers;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Contracts\ManagesActivity as ManagesActivityContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\ManagesStableMembers;
use App\Repositories\Contracts\StableRepositoryInterface;
use App\Repositories\Support\BaseRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class StableRepository extends BaseRepository implements ManagesActivityContract, ManagesRetirementContract, ManagesStableMembers, StableRepositoryInterface
{
    /** @use ManagesActivity<StableActivityPeriod, Stable> */
    use ManagesActivity;

    /** @use ManagesMembers<StableMember, Stable> */
    use ManagesMembers {
        ManagesMembers::addMember as addMemberToGroup;
        ManagesMembers::removeMember as removeMemberFromGroup;
    }

    /** @use ManagesRetirement<StableRetirement, Stable> */
    use ManagesRetirement;

    /**
     * Create a new stable.
     */
    public function create(StableData $stableData): Stable
    {
        return Stable::query()->create([
            'name' => $stableData->name,
        ]);
    }

    /**
     * Update a stable.
     */
    public function update(Stable $stable, StableData $stableData): Stable
    {
        $stable->update([
            'name' => $stableData->name,
        ]);

        return $stable;
    }

    /**
     * Add a wrestler to the stable.
     */
    public function addWrestler(Stable $stable, Wrestler $wrestler, Carbon $joinDate): void
    {
        $this->addMemberToGroup($stable, 'wrestlers', $wrestler, $joinDate);
    }

    /**
     * Remove a wrestler from the stable.
     */
    public function removeWrestler(Stable $stable, Wrestler $wrestler, Carbon $removalDate): void
    {
        $this->removeMemberFromGroup($stable, 'wrestlers', $wrestler, $removalDate);
    }

    /**
     * Add a tag team to the stable.
     */
    public function addTagTeam(Stable $stable, TagTeam $tagTeam, Carbon $joinDate): void
    {
        $this->addMemberToGroup($stable, 'tagTeams', $tagTeam, $joinDate);
    }

    /**
     * Remove a tag team from the stable.
     */
    public function removeTagTeam(Stable $stable, TagTeam $tagTeam, Carbon $removalDate): void
    {
        $this->removeMemberFromGroup($stable, 'tagTeams', $tagTeam, $removalDate);
    }

    /**
     * Add a manager to the stable.
     *
     * @deprecated Managers are now associated through wrestlers/tag teams
     */
    public function addManager(Stable $stable, Manager $manager, Carbon $joinDate): void
    {
        // No-op: Managers are now associated through wrestlers/tag teams
    }

    /**
     * Remove a manager from the stable.
     *
     * @deprecated Managers are now associated through wrestlers/tag teams
     */
    public function removeManager(Stable $stable, Manager $manager, Carbon $removalDate): void
    {
        // No-op: Managers are now associated through wrestlers/tag teams
    }

    // Bulk operations
    /**
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function addWrestlers(Stable $stable, Collection $wrestlers, Carbon $joinDate): void
    {
        $wrestlers->each(function (Wrestler $wrestler) use ($stable, $joinDate): void {
            $this->addWrestler($stable, $wrestler, $joinDate);
        });
    }

    /**
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function addTagTeams(Stable $stable, Collection $tagTeams, Carbon $joinDate): void
    {
        $tagTeams->each(function (TagTeam $tagTeam) use ($stable, $joinDate): void {
            $this->addTagTeam($stable, $tagTeam, $joinDate);
        });
    }

    /**
     * @param  Collection<int, Manager>  $managers
     *
     * @deprecated Managers are now associated through wrestlers/tag teams
     */
    public function addManagers(Stable $stable, Collection $managers, Carbon $joinDate): void
    {
        // No-op: Managers are now associated through wrestlers/tag teams
    }

    /**
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function removeWrestlers(Stable $stable, Collection $wrestlers, Carbon $removalDate): void
    {
        $wrestlers->each(function (Wrestler $wrestler) use ($stable, $removalDate): void {
            $this->removeWrestler($stable, $wrestler, $removalDate);
        });
    }

    /**
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function removeTagTeams(Stable $stable, Collection $tagTeams, Carbon $removalDate): void
    {
        $tagTeams->each(function (TagTeam $tagTeam) use ($stable, $removalDate): void {
            $this->removeTagTeam($stable, $tagTeam, $removalDate);
        });
    }

    /**
     * @param  Collection<int, Manager>  $managers
     *
     * @deprecated Managers are now associated through wrestlers/tag teams, no direct removal needed
     */
    public function removeManagers(Stable $stable, Collection $managers, Carbon $removalDate): void
    {
        // No-op: Managers are now associated through wrestlers/tag teams
        // Their associations automatically end when wrestler/tag team memberships end
    }

    /**
     * Remove all members from the stable.
     */
    public function disbandMembers(Stable $stable, Carbon $disbandDate): void
    {
        $this->disassembleAllMembers($stable, $disbandDate);
    }

    /**
     * Disassemble all members from the stable.
     */
    public function disassembleAllMembers(Stable $stable, Carbon $disassembleDate): void
    {
        $stable->currentWrestlers()->each(function (Wrestler $wrestler) use ($stable, $disassembleDate): void {
            $this->removeWrestler($stable, $wrestler, $disassembleDate);
        });

        $stable->currentTagTeams()->each(function (TagTeam $tagTeam) use ($stable, $disassembleDate): void {
            $this->removeTagTeam($stable, $tagTeam, $disassembleDate);
        });

        // Manager associations automatically end when wrestler/tag team memberships end
        // No direct manager removal needed since managers are associated through wrestlers/tag teams
    }

    /**
     * Update a stable's members by adding new ones and removing those no longer needed.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     * @param  Collection<int, TagTeam>  $tagTeams
     * @param  Collection<int, Manager>  $managers  (deprecated - no longer used)
     */
    public function updateStableMembers(Stable $stable, Collection $wrestlers, Collection $tagTeams, Collection $managers, ?Carbon $date = null): void
    {
        $updateDate = $date ?? now();

        $this->updateMemberType($stable, 'currentWrestlers', $wrestlers, $updateDate, 'addWrestlers', 'removeWrestlers');
        $this->updateMemberType($stable, 'currentTagTeams', $tagTeams, $updateDate, 'addTagTeams', 'removeTagTeams');
        // Manager parameter ignored - managers are now associated through wrestlers/tag teams
    }

    /**
     * Update members of a specific type attached to a stable.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  Collection<int, T>  $newMembers
     */
    private function updateMemberType(
        Stable $stable,
        string $currentRelation,
        Collection $newMembers,
        Carbon $date,
        string $addMethod,
        string $removeMethod
    ): void {
        $currentMembers = $stable->{$currentRelation};

        $membersToRemove = $currentMembers->diff($newMembers);
        $membersToAdd = $newMembers->diff($currentMembers);

        if ($membersToRemove->isNotEmpty()) {
            $this->{$removeMethod}($stable, $membersToRemove, $date);
        }

        if ($membersToAdd->isNotEmpty()) {
            $this->{$addMethod}($stable, $membersToAdd, $date);
        }
    }

    /**
     * Debut a stable by creating an activity period.
     */
    public function createDebut(Stable $stable, Carbon $debutDate): void
    {
        $this->createActivity($stable, $debutDate);
        
        // Update the stable status based on debut date
        $status = $debutDate->isFuture() ? StableStatus::PendingEstablishment : StableStatus::Active;
        $stable->update(['status' => $status]);
    }

    /**
     * Override endActivity to also update stable status when disbanded.
     */
    public function endActivity(HasActivityPeriods $model, Carbon $endDate): void
    {
        // Call the trait method to end the activity period
        $model->currentActivityPeriod()->update(['ended_at' => $endDate->toDateTimeString()]);
        
        // Update the stable status to Inactive when disbanded
        if ($model instanceof Stable) {
            $model->update(['status' => StableStatus::Inactive]);
        }
    }

    /**
     * Pull a stable by creating a reinstatement activity period.
     */
    public function pull(Stable $stable, Carbon $pullDate): void
    {
        $this->endActivity($stable, $pullDate);
        
        // Update the stable status to Inactive when pulled
        $stable->update(['status' => StableStatus::Inactive]);
    }

    /**
     * Reinstate a stable by creating a new activity period.
     */
    public function createReinstatement(Stable $stable, Carbon $reinstatementDate): void
    {
        $this->createActivity($stable, $reinstatementDate);
        
        // Update the stable status based on reinstatement date
        $status = $reinstatementDate->isFuture() ? StableStatus::PendingEstablishment : StableStatus::Active;
        $stable->update(['status' => $status]);
    }

    /**
     * Establish a stable by creating an activity period.
     */
    public function createEstablishment(Stable $stable, Carbon $establishmentDate): void
    {
        $this->createActivity($stable, $establishmentDate);
    }

    /**
     * Add a member to the stable.
     */
    public function addMember(Stable $stable, Wrestler|TagTeam|Manager $member, Carbon $startDate): void
    {
        // Determine the relationship type based on the member type
        // @phpstan-ignore-next-line instanceof.alwaysTrue
        $relationship = match (true) {
            $member instanceof Wrestler => 'wrestlers',
            $member instanceof TagTeam => 'tagTeams',
            $member instanceof Manager => null, // Managers no longer directly join stables
            default => throw new InvalidArgumentException('Invalid member type for stable'),
        };

        if ($relationship !== null) {
            $this->addMemberToGroup($stable, $relationship, $member, $startDate);
        }
        // Managers are ignored - they are associated through wrestlers/tag teams
    }

    /**
     * Remove a member from the stable.
     */
    public function removeMember(Stable $stable, Wrestler|TagTeam|Manager $member, Carbon $endDate): void
    {
        // Determine the relationship type based on the member type
        // @phpstan-ignore-next-line instanceof.alwaysTrue
        $relationship = match (true) {
            $member instanceof Wrestler => 'wrestlers',
            $member instanceof TagTeam => 'tagTeams',
            $member instanceof Manager => null, // Managers no longer directly leave stables
            default => throw new InvalidArgumentException('Invalid member type for stable'),
        };

        if ($relationship !== null) {
            $this->removeMemberFromGroup($stable, $relationship, $member, $endDate);
        }
        // Managers are ignored - their associations end automatically when wrestler/tag team memberships end
    }

    /**
     * Restore a soft-deleted stable.
     */
    public function restore(Stable $stable): void
    {
        $stable->restore();
    }

    /**
     * Get all managers associated with the stable through wrestlers and tag teams.
     *
     * @return Collection<int, Manager>
     */
    public function getAllAssociatedManagers(Stable $stable): Collection
    {
        $managers = collect();

        // Get managers from current wrestlers
        $stable->currentWrestlers()->each(function (Wrestler $wrestler) use ($managers): void {
            $wrestler->currentManagers->each(function (Manager $manager) use ($managers): void {
                $managers->push($manager);
            });
        });

        // Get managers from current tag teams
        $stable->currentTagTeams()->each(function (TagTeam $tagTeam) use ($managers): void {
            $tagTeam->currentManagers->each(function (Manager $manager) use ($managers): void {
                $managers->push($manager);
            });
        });

        return $managers->unique('id');
    }
}
