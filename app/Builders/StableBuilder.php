<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Models\Stable
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModelClass>
 */
class StableBuilder extends Builder
{
    use Concerns\HasActivations;
    use Concerns\HasRetirements;
}
