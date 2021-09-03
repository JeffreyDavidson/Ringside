<?php

namespace App\Models\Concerns;

use App\Models\Stable;

trait StableMember
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

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
     * @return \Staudenmeir\EloquentHasManyDeep\HasRelationships\HasOneDeep
     */
    public function currentStable()
    {
        return $this
            ->hasOneDeep(
                Stable::class,
                [static::class, 'stable_members'],
                ['id', ['member_type', 'member_id'], 'id'],
                [null, null, 'stable_id']
            )
            ->whereNull('left_at');
    }

    /**
     * Get the previous stables the member has belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousStables()
    {
        return $this->morphMany(Stable::class, 'members')
                    ->wherePivot('joined_at', '<', now())
                    ->wherePivot('left_at', '!=', null);
    }
}
