<?php

declare(strict_types=1);

namespace App\Builders\Users;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the User model.
 *
 * This builder is currently empty but serves as a foundation for future
 * user-specific query scopes. As the User model evolves to include status
 * management, roles, or other filterable attributes, corresponding query
 * methods can be added here.
 *
 * @template TModel of User
 *
 * @extends Builder<TModel>
 *
 * @example
 * ```php
 * // Future usage examples:
 * // $activeUsers = User::query()->active()->get();
 * // $adminUsers = User::query()->withRole('admin')->get();
 * ```
 */
class UserBuilder extends Builder
{
    // Future query scopes will be added here as the User model evolves
}
