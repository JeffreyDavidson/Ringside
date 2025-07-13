<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Contract for repositories that manage retirement status.
 *
 * This interface defines the methods required for repositories
 * that handle retirement and unretirement operations.
 */
interface ManagesRetirement
{
    /**
     * End retirement for the given model.
     *
     * @param  Retirable<Model, Model>  $model
     */
    public function endRetirement(Retirable $model, Carbon $endDate): void;

    /**
     * Create retirement for the given model.
     *
     * @param  Retirable<Model, Model>  $model
     */
    public function createRetirement(Retirable $model, Carbon $startDate): void;
}
