<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Stable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Stable
 *
 * @extends Builder<TModel>
 */
class StableBuilder extends Builder {}
