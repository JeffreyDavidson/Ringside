<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModelClass>
 */
class StableBuilder extends Builder
{
    use Concerns\HasActivations;
    use Concerns\HasRetirements;
}
