<?php

namespace App\Models\Contracts;

interface Deactivatable
{
    /**
     * Deactivate a given deactivatable with a given date.
     *
     * @param  self $deactivatable
     * @param  string|null $endedAt
     * @return self $deactivatable
     */
    public function deactivate(self $deactivatable, string $endedAt = null);
}
