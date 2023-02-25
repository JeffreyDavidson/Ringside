<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface Deactivatable
{
    /**
     * Check to see if the model is currently deactivated.
     */
    public function isDeactivated(): bool;
}
