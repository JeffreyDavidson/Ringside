<?php

namespace App\Repositories\Contracts;

use App\Models\Contracts\Deactivatable;

interface DeactivationRepositoryInterface
{
    /**
     * Deactivate a deactivatable.
     *
     * @param  \App\Models\Contracts\Deactivatable $deactivatable
     * @param  string|null $endedAt
     * @return \App\Models\Contracts\Deactivatable $deactivatable
     */
    public function deactivate(Deactivatable $deactivatable, string $endedAt = null);
}
