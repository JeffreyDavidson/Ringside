<?php

namespace App\Models\Contracts;

interface CanJoinStable
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function stables();

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function currentStable();

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function previousStables();
}
