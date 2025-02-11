<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Stable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @phpstan-require-implements \App\Models\Contracts\CanBeAStableMember
 */
trait CanJoinStables
{
    /**
     * Get the previous stables the member has belonged to.
     *
     * @return BelongsToMany<Stable, $this>
     */
    public function previousStables(): BelongsToMany
    {
        return $this->stables()
            ->wherePivot('joined_at', '<', now())
            ->wherePivotNotNull('left_at');
    }

    /**
     * Determine if the model is currently a member of a stable.
     */
    public function isNotCurrentlyInStable(Stable $stable): bool
    {
        return $this->currentStable()->getParent()->isNot($stable);
    }
}
