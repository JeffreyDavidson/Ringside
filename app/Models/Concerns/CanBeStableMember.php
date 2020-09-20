<?php

namespace App\Models\Concerns;

use App\Models\Member;
use App\Models\Stable;

trait CanBeStableMember
{
    /**
     * Get the stables the model has been belonged to.
     *
     * @return App\Eloquent\Relationships\MorphMany
     */
    public function stables()
    {
        return $this->morphToMany(Stable::class, 'member')
                    ->using(Member::class);
    }

    /**
     * Get the current stable the member belongs to.
     *
     * @return App\Eloquent\Relationships\MorphOne
     */
    public function currentStable()
    {
        return $this->morphOne(Stable::class, 'members')
                    ->wherePivot('joined_at', '<=', now())
                    ->wherePivot('left_at', '=', null)
                    ->limit(1);
    }

    /**
     * Get the previous stables the member has belonged to.
     *
     * @return App\Eloquent\Relationships\MorphToMany
     */
    public function previousStables()
    {
        return $this->morphMany(Stable::class, 'members')
                    ->wherePivot('joined_at', '<', now())
                    ->wherePivot('left_at', '!=', null);
    }
}
