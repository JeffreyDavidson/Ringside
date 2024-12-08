<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\ManagerStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\Manager
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
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
