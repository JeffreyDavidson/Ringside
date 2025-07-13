<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Contracts\HasActivityPeriods;
use Illuminate\Support\Carbon;

/**
 * Contract for repositories that manage activity status.
 *
 * This interface defines the methods required for repositories
 * that handle activation and deactivation operations.
 */
interface ManagesActivity
{
    /**
     * End activity for the given model.
     */
    public function endActivity(HasActivityPeriods $model, Carbon $endDate): void;

    /**
     * Create activity for the given model.
     */
    public function createActivity(HasActivityPeriods $model, Carbon $startDate): void;
}
