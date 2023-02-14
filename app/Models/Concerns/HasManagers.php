<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Manager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasManagers
{
    /**
     * Get all of the managers the model has had.
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class);
    }

    /**
     * Get all of the managers the model has had.
     */
    public function currentManagers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class)
            ->wherePivotNull('left_at');
    }

    /**
     * Get all of the managers the model has had.
     */
    public function previousManagers(): BelongsToMany
    {
        return $this->belongsToMany(Manager::class)
            ->wherePivotNotNull('left_at');
    }
}
