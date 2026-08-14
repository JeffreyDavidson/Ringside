<?php

declare(strict_types=1);

namespace App\Builders\Users;

use App\Builders\Concerns\HasNameSearch;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of User
 *
 * @extends Builder<TModel>
 */
class UserBuilder extends Builder
{
    use HasNameSearch;
}
