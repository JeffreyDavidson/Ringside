<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\Stable
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
 */
class StableBuilder extends Builder {}
