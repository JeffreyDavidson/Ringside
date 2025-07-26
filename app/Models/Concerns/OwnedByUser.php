<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Simple trait for models that belong to a user.
 *
 * This trait provides a lightweight user relationship as an alternative
 * to the more comprehensive BelongsToUser trait. It establishes a basic
 * belongs-to relationship with explicit foreign key specification.
 *
 * @example
 * ```php
 * class SomeModel extends Model
 * {
 *     use OwnedByUser;
 * }
 *
 * $model = SomeModel::find(1);
 * $owner = $model->user;
 * ```
 */
trait OwnedByUser
{
    /**
     * Get the user that owns this model.
     *
     * Establishes a belongs-to relationship with the User model using
     * the 'user_id' foreign key column.
     *
     * @return BelongsTo<User, static>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
