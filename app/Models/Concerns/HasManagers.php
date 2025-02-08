<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
