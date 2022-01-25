<?php

namespace App\Models\Concerns;

use App\Models\Stable;
use Fidum\EloquentMorphToOne\HasMorphToOne;

trait CanJoinStables
{
    use HasMorphToOne;

    /**
     * Get the stables the model has been belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stables()
    {
        return $this->morphToMany(Stable::class, 'member', 'stable_members')
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get the current stable the member belongs to.
     *
     * @return \App\Models\Stable|null
     */
    public function currentStable()
    {
        return $this->stables()
            ->withPivot(['joined_at', 'left_at'])
            ->wherePivotNull('left_at')
            ->first();
    }

    /**
     * Get the previous stables the member has belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function previousStables()
    {
        return $this->stables()
            ->wherePivot('joined_at', '<', now())
            ->wherePivotNotNull('left_at');
    }

    /**
     * Undocumented function
     *
     * @param  \App\Models\Stable $stable
     * @return bool
     */
    public function isNotCurrentlyInStable(Stable $stable)
    {
        return ! $this->currentStable() || $this->currentStable()->isNot($stable);
    }
}
