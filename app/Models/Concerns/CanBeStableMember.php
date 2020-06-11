<?php

namespace App\Models\Concerns;

use App\Models\Member;
use App\Models\Stable;

/**
 * @mixin \App\Models\Concerns\HasCustomRelationships
 */
trait CanBeStableMember
{
    /**
     * Get the stable history the member has belonged to.
     *
     * @return App\Eloquent\Relationships\LeaveableMorphToMany
     */
    public function stableHistory()
    {
        return $this->leaveableMorphToMany(Stable::class, 'member')->using(Member::class);
    }

    /**
     * Get the current stable the member belongs to.
     *
     * @return App\Eloquent\Relationships\LeaveableMorphToMany
     */
    public function currentStable()
    {
        return $this->stableHistory()->where('status', 'active')->current()->latest();
    }

    /**
     * Get the previous stables the member has belonged to.
     *
     * @return App\Eloquent\Relationships\LeaveableMorphToMany
     */
    public function previousStables()
    {
        return $this->stableHistory()->detached();
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getCurrentStableAttribute()
    {
        if (!$this->relationLoaded('currentStable')) {
            $this->setRelation('currentStable', $this->currentStable()->get());
        }

        return $this->getRelation('currentStable')->first();
    }
}
