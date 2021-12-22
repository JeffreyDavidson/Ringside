<?php

namespace App\Models\Contracts;

interface Bookable
{
    /**
     * Check to see if the model is bookable.
     *
     * @return bool
     */
    public function isBookable();
}
