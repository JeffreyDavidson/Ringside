<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\Stables\StableData;
use App\Models\Contracts\HasActivityPeriods;
use App\Models\Contracts\Retirable;
use App\Models\Managers\Manager;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

interface StableRepositoryInterface
{
    // CRUD operations
    public function create(StableData $stableData): Stable;

    public function update(Stable $stable, StableData $stableData): Stable;

    public function delete(Stable $stable): void;

    public function restore(Stable $stable): void;

    // Activity operations
    public function createActivity(HasActivityPeriods $stable, Carbon $startDate): void;

    public function endActivity(HasActivityPeriods $stable, Carbon $endDate): void;

    // Retirement operations
    /**
     * @param  Retirable<Model, Model>  $stable
     */
    public function createRetirement(Retirable $stable, Carbon $startDate): void;

    /**
     * @param  Retirable<Model, Model>  $stable
     */
    public function endRetirement(Retirable $stable, Carbon $endDate): void;

    // Member operations - Individual
    public function addWrestler(Stable $stable, Wrestler $wrestler, Carbon $joinDate): void;

    public function removeWrestler(Stable $stable, Wrestler $wrestler, Carbon $removalDate): void;

    public function addTagTeam(Stable $stable, TagTeam $tagTeam, Carbon $joinDate): void;

    public function removeTagTeam(Stable $stable, TagTeam $tagTeam, Carbon $removalDate): void;

    public function addManager(Stable $stable, Manager $manager, Carbon $joinDate): void;

    public function removeManager(Stable $stable, Manager $manager, Carbon $removalDate): void;

    // Member operations - Bulk
    /**
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function addWrestlers(Stable $stable, Collection $wrestlers, Carbon $joinDate): void;

    /**
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function addTagTeams(Stable $stable, Collection $tagTeams, Carbon $joinDate): void;

    /**
     * @param  Collection<int, Manager>  $managers
     */
    public function addManagers(Stable $stable, Collection $managers, Carbon $joinDate): void;

    /**
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function removeWrestlers(Stable $stable, Collection $wrestlers, Carbon $removalDate): void;

    /**
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function removeTagTeams(Stable $stable, Collection $tagTeams, Carbon $removalDate): void;

    /**
     * @param  Collection<int, Manager>  $managers
     */
    public function removeManagers(Stable $stable, Collection $managers, Carbon $removalDate): void;

    // Disassembly operations
    public function disassembleAllMembers(Stable $stable, Carbon $disassembleDate): void;

    // Domain-specific activity operations
    public function createEstablishment(Stable $stable, Carbon $establishmentDate): void;
}
