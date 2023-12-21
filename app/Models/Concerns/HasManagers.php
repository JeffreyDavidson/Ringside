<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Manager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasManagers
{
    /**
     * Get all the managers the model has had.
     *
     * @return BelongsToMany<Manager>
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class);
    }

    /**
     * Get all the managers the model has had.
     *
     * @return BelongsToMany<Manager>
     */
    public function currentManagers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class)
            ->wherePivotNull('left_at');
    }

    /**
     * Get all the managers the model has had.
     *
     * @return BelongsToMany<Manager>
     */
    public function previousManagers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class)
            ->wherePivotNotNull('left_at');
    }
}
