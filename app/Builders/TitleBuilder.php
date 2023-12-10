<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\TitleStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModelClass>
 */
class TitleBuilder extends Builder
{
    use Concerns\HasActivations;
    use Concerns\HasRetirements;

    /**
     * Scope a query to include competable titles.
     */
    public function competable(): self
    {
        $this->where('status', TitleStatus::Active);

        return $this;
    }
}
