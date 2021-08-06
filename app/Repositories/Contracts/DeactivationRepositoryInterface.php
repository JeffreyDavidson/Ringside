<?php

namespace App\Repositories\Contracts;

use App\Models\Contracts\Deactivatable;

interface DeactivationRepositoryInterface
{
    /**
     * Deactivate a deactivatable.
     *
     * @param  \App\Models\Contracts\Deactivatable $deactivatable
     * @param  string $deactivationDate
     * @return \App\Models\Contracts\Deactivatable $deactivatable
     */
    public function deactivate(Deactivatable $deactivatable, string $deactivationDate);
}
