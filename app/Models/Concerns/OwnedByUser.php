<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait OwnedByUser
{
    /**
     * Get the user assigned to the model.
     *
     * @return BelongsTo<User, covariant Model>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
