<?php

namespace App\Models\Contracts;

interface Deactivatable
{
    /**
     * Check to see if the model is currently deactivated.
     *
     * @return bool
     */
    public function isDeactivated();
}
