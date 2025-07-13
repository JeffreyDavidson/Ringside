<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for models that belong to a user.
 *
 * This trait provides a standardized way for models to establish
 * a belongs-to relationship with a User model.
 */
trait BelongsToUser
{
    /**
     * Get the user that owns this model.
     *
     * @return BelongsTo<User, static>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
