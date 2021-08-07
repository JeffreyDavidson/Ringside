<?php

namespace App\Models\Contracts;

interface CanBeDisbanded
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function canBeDisbanded();
}
