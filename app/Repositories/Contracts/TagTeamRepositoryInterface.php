<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\TagTeams\TagTeamData;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

interface TagTeamRepositoryInterface
{
    // CRUD operations
    public function create(TagTeamData $tagTeamData): TagTeam;

    public function update(TagTeam $tagTeam, TagTeamData $tagTeamData): TagTeam;

    public function delete(TagTeam $tagTeam): void;

    public function restore(TagTeam $tagTeam): void;

    // Employment operations
    /**
     * @param  Employable<Model, Model>  $tagTeam
     */
    public function createEmployment(Employable $tagTeam, Carbon $startDate): void;

    /**
     * @param  Employable<Model, Model>  $tagTeam
     */
    public function endEmployment(Employable $tagTeam, Carbon $endDate): void;

    // Retirement operations
    /**
     * @param  Retirable<Model, Model>  $tagTeam
     */
    public function createRetirement(Retirable $tagTeam, Carbon $startDate): void;

    /**
     * @param  Retirable<Model, Model>  $tagTeam
     */
    public function endRetirement(Retirable $tagTeam, Carbon $endDate): void;

    // Suspension operations
    /**
     * @param  Suspendable<Model, Model>  $tagTeam
     */
    public function createSuspension(Suspendable $tagTeam, Carbon $startDate): void;

    /**
     * @param  Suspendable<Model, Model>  $tagTeam
     */
    public function endSuspension(Suspendable $tagTeam, Carbon $endDate): void;

    // Member operations
    public function addWrestler(TagTeam $tagTeam, Wrestler $wrestler, Carbon $joinDate): void;

    public function removeWrestler(TagTeam $tagTeam, Wrestler $wrestler, Carbon $removalDate): void;

    /**
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function addWrestlers(TagTeam $tagTeam, Collection $wrestlers, Carbon $joinDate): void;

    /**
     * @param  Collection<int, Wrestler>  $formerPartners
     * @param  Collection<int, Wrestler>  $newPartners
     */
    public function syncWrestlers(TagTeam $tagTeam, Collection $formerPartners, Collection $newPartners, Carbon $date): void;
}
