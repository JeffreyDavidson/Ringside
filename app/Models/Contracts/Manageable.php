<?php

namespace App\Models\Contracts;

interface Manageable
{
    /**
     * Get all of the managers of the model.
     */
    public function managers();

    /**
     * Undocumented function.
     */
    public function currentManagers();

    /**
     * Undocumented function.
     */
    public function previousManagers();
}
