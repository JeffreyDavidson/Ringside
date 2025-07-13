<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Contract for repositories that manage suspension status.
 *
 * This interface defines the methods required for repositories
 * that handle suspension and reinstatement operations.
 */
interface ManagesSuspension
{
    /**
     * End suspension for the given model.
     *
     * @param  Suspendable<Model, Model>  $model
     */
    public function endSuspension(Suspendable $model, Carbon $endDate): void;

    /**
     * Create suspension for the given model.
     *
     * @param  Suspendable<Model, Model>  $model
     */
    public function createSuspension(Suspendable $model, Carbon $startDate): void;
}
