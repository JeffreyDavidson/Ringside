<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\ManagerStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Models\Manager
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModelClass>
 */
class ManagerBuilder extends Builder
{
    use Concerns\HasEmployments;
    use Concerns\HasInjuries;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;

    /**
     * Scope a query to include available managers.
     */
    public function available(): static
    {
        $this->where('status', ManagerStatus::Available);

        return $this;
    }
}
