<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Contracts\Employable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Contract for repositories that manage employment status.
 *
 * This interface defines the methods required for repositories
 * that handle employment, release, and reinstatement operations.
 */
interface ManagesEmployment
{
    /**
     * End employment for the given model.
     *
     * @param  Employable<Model, Model>  $model
     */
    public function endEmployment(Employable $model, Carbon $endDate): void;

    /**
     * Create employment for the given model.
     *
     * @param  Employable<Model, Model>  $model
     */
    public function createEmployment(Employable $model, Carbon $startDate): void;
}
