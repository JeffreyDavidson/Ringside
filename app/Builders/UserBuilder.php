<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of User
 *
 * @extends Builder<TModel>
 */
class UserBuilder extends Builder {}
