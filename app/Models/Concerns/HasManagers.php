<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @phpstan-require-implements \App\Models\Contracts\Manageable
 */
trait HasManagers
{
    public function currentManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivotNull('left_at');
    }

    public function previousManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivotNotNull('left_at');
    }
}
