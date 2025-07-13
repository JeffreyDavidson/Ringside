<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Contracts\Injurable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Contract for repositories that manage injury status.
 *
 * This interface defines the methods required for repositories
 * that handle injury and recovery operations.
 */
interface ManagesInjury
{
    /**
     * End injury for the given model.
     *
     * @param  Injurable<Model, Model>  $model
     */
    public function endInjury(Injurable $model, Carbon $endDate): void;

    /**
     * Create injury for the given model.
     *
     * @param  Injurable<Model, Model>  $model
     */
    public function createInjury(Injurable $model, Carbon $startDate): void;
}
