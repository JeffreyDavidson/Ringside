<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\ManagerStatus;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Manager
 *
 * @extends Builder<TModel>
 */
class ManagerBuilder extends Builder
{
    /**
     * Scope a query to include available managers.
     */
    public function available(): static
    {
        $this->where('status', ManagerStatus::Available);

        return $this;
    }
}
