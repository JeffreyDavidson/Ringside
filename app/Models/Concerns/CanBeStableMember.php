<?php

namespace App\Models\Concerns;

use App\Models\Member;
use App\Models\Stable;

trait CanBeStableMember
{
    /**
     * Get the stables the model has been belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function stables()
    {
        return $this->morphToMany(Stable::class, 'member', 'stable_members');
    }

    /**
     * Get the current stable the member belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentStable()
    {
        return $this->morphToOne(Stable::class, 'member', 'stable_members')
                    ->wherePivot('joined_at', '<=', now())
                    ->wherePivot('left_at', '=', null)
                    ->limit(1);
    }

    /**
     * Get the previous stables the member has belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function previousStables()
    {
        return $this->morphMany(Stable::class, 'members')
                    ->wherePivot('joined_at', '<', now())
                    ->wherePivot('left_at', '!=', null);
    }
}
