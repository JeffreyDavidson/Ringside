<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface Bookable
{
    /**
     * Check to see if the model is bookable.
     */
    public function isBookable(): bool;
}
