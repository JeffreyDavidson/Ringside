<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Stable;
use Fidum\EloquentMorphToOne\HasMorphToOne;
use Fidum\EloquentMorphToOne\MorphToOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property ?Stable $currentStable
 */
trait CanJoinStables
{
    use HasMorphToOne;

    /**
     * Get the stables the model has been belonged to.
     */
    public function stables(): MorphToMany
    {
        return $this->morphToMany(Stable::class, 'member', 'stable_members')
            ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get the current stable the member belongs to.
     */
    public function currentStable(): MorphToOne
    {
        return $this->morphToOne(Stable::class, 'member', 'stable_members')
            ->withPivot(['joined_at', 'left_at'])
            ->wherePivotNull('left_at');
    }

    /**
     * Get the previous stables the member has belonged to.
     */
    public function previousStables(): MorphToMany
    {
        return $this->stables()
            ->wherePivot('joined_at', '<', now())
            ->wherePivotNotNull('left_at');
    }

    /**
     * Undocumented function.
     */
    public function isNotCurrentlyInStable(Stable $stable): bool
    {
        return ! $this->currentStable || $this->currentStable->isNot($stable);
    }
}
