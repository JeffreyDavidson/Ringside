<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @phpstan-require-implements \App\Models\Contracts\Manageable
 */
trait HasManagers
{
    /**
     * @return BelongsToMany<Manager, static>
     */
    public function currentManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivotNull('left_at');
    }

    /**
     * @return BelongsToMany<Manager, static>
     */
    public function previousManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivotNotNull('left_at');
    }
}
