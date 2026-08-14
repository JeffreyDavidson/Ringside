<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\Manageable;
use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @template TPivotModel of Pivot
 * @template TModel of Model
 *
 * @phpstan-require-implements Manageable<TPivotModel, TModel>
 */
trait HasManagerAssignments
{
    abstract protected function managerAssignmentTable(): string;

    /** @return class-string<TPivotModel> */
    abstract protected function managerAssignmentPivotModel(): string;

    /** @return BelongsToMany<Manager, static, TPivotModel> */
    public function managers(): BelongsToMany
    {
        /** @var BelongsToMany<Manager, static, TPivotModel> $relation */
        $relation = $this->belongsToMany(Manager::class, $this->managerAssignmentTable())
            ->using($this->managerAssignmentPivotModel())
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps();

        return $relation;
    }

    /** @return BelongsToMany<Manager, static, TPivotModel> */
    public function currentManagers(): BelongsToMany
    {
        return $this->managers()->wherePivotNull('fired_at');
    }

    /** @return BelongsToMany<Manager, static, TPivotModel> */
    public function previousManagers(): BelongsToMany
    {
        return $this->managers()->wherePivotNotNull('fired_at');
    }
}
