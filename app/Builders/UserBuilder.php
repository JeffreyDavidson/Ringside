<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Models\User
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModelClass>
 */
class UserBuilder extends Builder {}
