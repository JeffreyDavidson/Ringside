<?php

namespace App\Repositories;

use App\Models\Contracts\Deactivatable;
use Carbon\Carbon;

interface DeactivationRepositoryInterface
{
    /**
     * Deactivate a deactivatable.
     *
     * @param  \Carbon\Carbon $startedAt
     * @return void
     */
    public function deactivate(Deactivatable $deactivatable, Carbon $startedAt = null);
}
