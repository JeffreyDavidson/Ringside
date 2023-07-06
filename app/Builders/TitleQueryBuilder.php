<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\TitleStatus;
use Illuminate\Database\Eloquent\Builder;

class TitleQueryBuilder extends Builder
{
    use Concerns\HasActivations;
    use Concerns\HasRetirements;

    /**
     * Scope a query to only include competable titles.
     */
    public function competable(): self
    {
        return $this->where('status', TitleStatus::ACTIVE);
    }
}
