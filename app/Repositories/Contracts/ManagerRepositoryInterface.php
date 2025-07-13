<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\Managers\ManagerData;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

interface ManagerRepositoryInterface
{
    // CRUD operations
    public function create(ManagerData $managerData): Manager;

    public function update(Manager $manager, ManagerData $managerData): Manager;

    public function delete(Manager $manager): void;

    public function restore(Manager $manager): void;

    // Employment operations
    /**
     * @param  Employable<Model, Model>  $manager
     */
    public function createEmployment(Employable $manager, Carbon $startDate): void;

    /**
     * @param  Employable<Model, Model>  $manager
     */
    public function endEmployment(Employable $manager, Carbon $endDate): void;

    // Injury operations
    /**
     * @param  Injurable<Model, Model>  $manager
     */
    public function createInjury(Injurable $manager, Carbon $startDate): void;

    /**
     * @param  Injurable<Model, Model>  $manager
     */
    public function endInjury(Injurable $manager, Carbon $endDate): void;

    // Retirement operations
    /**
     * @param  Retirable<Model, Model>  $manager
     */
    public function createRetirement(Retirable $manager, Carbon $startDate): void;

    /**
     * @param  Retirable<Model, Model>  $manager
     */
    public function endRetirement(Retirable $manager, Carbon $endDate): void;

    // Suspension operations
    /**
     * @param  Suspendable<Model, Model>  $manager
     */
    public function createSuspension(Suspendable $manager, Carbon $startDate): void;

    /**
     * @param  Suspendable<Model, Model>  $manager
     */
    public function endSuspension(Suspendable $manager, Carbon $endDate): void;

    // Relationship operations
    public function removeFromCurrentTagTeams(Manager $manager, Carbon $removalDate): void;

    public function removeFromCurrentWrestlers(Manager $manager, Carbon $removalDate): void;
}
