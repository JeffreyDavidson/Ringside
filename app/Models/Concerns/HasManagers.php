<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Manager;

trait HasManagers
{
    /**
     * Get all of the managers the model has had.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function managers()
    {
        return $this->belongsToMany(Manager::class);
    }

    /**
     * Get all of the managers the model has had.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function currentManagers()
    {
        return $this->belongsToMany(Manager::class)
            ->wherePivotNull('left_at');
    }

    /**
     * Get all of the managers the model has had.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousManagers()
    {
        return $this->belongsToMany(Manager::class)
            ->wherePivotNotNull('left_at');
    }
}
