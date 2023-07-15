<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\ManagerStatus;
use Illuminate\Database\Eloquent\Builder;

class ManagerBuilder extends Builder
{
    use Concerns\HasEmployments;
    use Concerns\HasInjuries;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;

    /**
     * Scope a query to only include available managers.
     */
    public function available(): self
    {
        return $this->where('status', ManagerStatus::AVAILABLE);
    }
}
