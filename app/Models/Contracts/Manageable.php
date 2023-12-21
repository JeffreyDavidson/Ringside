<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Manager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Manageable
{
    /**
     * Get all the managers of the model.
     *
     * @return BelongsToMany<Manager>
     */
    public function managers(): BelongsToMany;

    /**
     * Get the current managers of the model.
     *
     * @return BelongsToMany<Manager>
     */
    public function currentManagers(): BelongsToMany;

    /**
     * Get the previous managers of the model.
     *
     * @return BelongsToMany<Manager>
     */
    public function previousManagers(): BelongsToMany;
}
