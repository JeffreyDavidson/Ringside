<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Manageable
{
    /**
     * Get all of the managers of the model.
     */
    public function managers(): BelongsToMany;

    /**
     * Get the current managers of the model.
     */
    public function currentManagers(): BelongsToMany;

    /**
     * Get the previous managers of the model.
     */
    public function previousManagers(): BelongsToMany;
}
