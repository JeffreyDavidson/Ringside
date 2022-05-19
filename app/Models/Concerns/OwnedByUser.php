<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;

trait OwnedByUser
{
    /**
     * Get the user assigned to the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
