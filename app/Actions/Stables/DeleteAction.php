<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Roster\Stables\Stable;
use App\Services\StableDeletionService;

class DeleteAction
{
    public function __construct(
        private readonly StableDeletionService $deletion,
    ) {}

    /**
     * Delete a stable.
     *
     * The stable must already be inactive and have no current members. Those
     * transitions remain explicit operations so deletion only changes record state.
     *
     * @param  Stable  $stable  The stable to delete
     */
    public function handle(Stable $stable): void
    {
        $this->deletion->delete($stable, now());
    }
}
