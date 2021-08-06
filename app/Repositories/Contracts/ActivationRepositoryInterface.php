<?php

namespace App\Repositories\Contracts;

use App\Models\Contracts\Activatable;

interface ActivationRepositoryInterface
{
    /**
     * Activate an activatable.
     *
     * @param  \App\Models\Contracts\Activatable $activatable
     * @param  string|null $startedAt
     * @return \App\Models\Contracts\Activatable $activatable
     */
    public function activate(Activatable $activatable, string $startedAt = null);
}
