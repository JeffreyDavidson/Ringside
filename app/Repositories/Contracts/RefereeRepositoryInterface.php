<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\Referees\RefereeData;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Referees\Referee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

interface RefereeRepositoryInterface
{
    // CRUD operations
    public function create(RefereeData $refereeData): Referee;

    public function update(Referee $referee, RefereeData $refereeData): Referee;

    public function delete(Referee $referee): void;

    public function restore(Referee $referee): void;

    // Employment operations
    /**
     * @param  Employable<Model, Model>  $referee
     */
    public function createEmployment(Employable $referee, Carbon $startDate): void;

    /**
     * @param  Employable<Model, Model>  $referee
     */
    public function endEmployment(Employable $referee, Carbon $endDate): void;

    // Injury operations
    /**
     * @param  Injurable<Model, Model>  $referee
     */
    public function createInjury(Injurable $referee, Carbon $startDate): void;

    /**
     * @param  Injurable<Model, Model>  $referee
     */
    public function endInjury(Injurable $referee, Carbon $endDate): void;

    // Retirement operations
    /**
     * @param  Retirable<Model, Model>  $referee
     */
    public function createRetirement(Retirable $referee, Carbon $startDate): void;

    /**
     * @param  Retirable<Model, Model>  $referee
     */
    public function endRetirement(Retirable $referee, Carbon $endDate): void;

    // Suspension operations
    /**
     * @param  Suspendable<Model, Model>  $referee
     */
    public function createSuspension(Suspendable $referee, Carbon $startDate): void;

    /**
     * @param  Suspendable<Model, Model>  $referee
     */
    public function endSuspension(Suspendable $referee, Carbon $endDate): void;
}
