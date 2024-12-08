<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\User
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
 */
class UserBuilder extends Builder {}
