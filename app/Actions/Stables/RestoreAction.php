<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableDeletionService;

class RestoreAction
{
    public function __construct(
        private readonly StableDeletionService $deletion,
    ) {}

    /**
     * Restore a soft-deleted stable.
     *
     * This handles the stable restoration workflow:
     * - Validates the stable is deleted and has no active name conflict
     * - Restores the soft-deleted stable record from trash
     * - Preserves all historical member relationships and match history
     * - Leaves reunion and activation as explicit subsequent operations
     */
    public function handle(Stable $stable): void
    {
        $this->deletion->restore($stable, now());
    }
}
