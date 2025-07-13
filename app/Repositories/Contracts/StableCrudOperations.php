<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\Stables\StableData;
use App\Models\Stables\Stable;

/**
 * Interface for stable CRUD operations.
 */
interface StableCrudOperations
{
    /**
     * Create a new stable.
     */
    public function create(StableData $stableData): Stable;

    /**
     * Update an existing stable.
     */
    public function update(Stable $stable, StableData $stableData): Stable;

    /**
     * Delete a stable.
     */
    public function delete(Stable $stable): bool;
}
