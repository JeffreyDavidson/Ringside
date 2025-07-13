<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\Wrestlers\WrestlerData;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

interface WrestlerRepositoryInterface
{
    // CRUD operations
    public function create(WrestlerData $wrestlerData): Wrestler;

    public function update(Wrestler $wrestler, WrestlerData $wrestlerData): Wrestler;

    public function delete(Wrestler $wrestler): void;

    public function restore(Wrestler $wrestler): void;

    // Employment operations
    /**
     * @param  Employable<Model, Model>  $wrestler
     */
    public function createEmployment(Employable $wrestler, Carbon $startDate): void;

    /**
     * @param  Employable<Model, Model>  $wrestler
     */
    public function endEmployment(Employable $wrestler, Carbon $endDate): void;

    // Injury operations
    /**
     * @param  Injurable<Model, Model>  $wrestler
     */
    public function createInjury(Injurable $wrestler, Carbon $startDate): void;

    /**
     * @param  Injurable<Model, Model>  $wrestler
     */
    public function endInjury(Injurable $wrestler, Carbon $endDate): void;

    // Retirement operations
    /**
     * @param  Retirable<Model, Model>  $wrestler
     */
    public function createRetirement(Retirable $wrestler, Carbon $startDate): void;

    /**
     * @param  Retirable<Model, Model>  $wrestler
     */
    public function endRetirement(Retirable $wrestler, Carbon $endDate): void;

    // Suspension operations
    /**
     * @param  Suspendable<Model, Model>  $wrestler
     */
    public function createSuspension(Suspendable $wrestler, Carbon $startDate): void;

    /**
     * @param  Suspendable<Model, Model>  $wrestler
     */
    public function endSuspension(Suspendable $wrestler, Carbon $endDate): void;

    // Relationship operations
    public function removeFromCurrentTagTeam(Wrestler $wrestler, Carbon $removalDate): void;

    // Query operations
    /**
     * @return Collection<int, covariant Wrestler>
     */
    public function getAvailableForNewTagTeam(): Collection;

    /**
     * @return Collection<int, covariant Wrestler>
     */
    public function getAvailableForExistingTagTeam(TagTeam $tagTeam): Collection;

    /**
     * @return Collection<int, covariant Wrestler>
     */
    public function getAvailableForBooking(): Collection;

    /**
     * @return Collection<int, covariant Wrestler>
     */
    public function getUnemployed(): Collection;

    /**
     * @return Collection<int, covariant Wrestler>
     */
    public function getFutureEmployed(): Collection;

    /**
     * @return Collection<int, covariant Wrestler>
     */
    public function getInTagTeam(TagTeam $tagTeam): Collection;
}
