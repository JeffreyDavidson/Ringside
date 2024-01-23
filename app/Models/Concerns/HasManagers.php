<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Managable;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasManagers
{
    /**
     * Get all the managers the model has had.
     *
     * @return MorphToMany<Manager>
     */
    public function managers(): MorphToMany
    {
        return $this->morphToMany(Manager::class, 'manageable')
            ->withPivot('hired_at', 'left_at')
            ->using(Managable::class);
    }

    /**
     * Get all the current managers the model has.
     *
     * @return MorphToMany<Manager>
     */
    public function currentManagers(): MorphToMany
    {
        return $this->managers()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all the previous managers the model has had.
     *
     * @return MorphToMany<Manager>
     */
    public function previousManagers(): MorphToMany
    {
        return $this->managers()
            ->wherePivotNotNull('left_at');
    }
}
