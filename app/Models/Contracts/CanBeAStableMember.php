<?php

namespace App\Models\Contracts;

interface CanBeAStableMember
{
    /**
     * Get the stables the stable member has been a member of.
     */
    public function stables();

    /**
     * Get the current stable the member belongs to.
     */
    public function currentStable();

    /**
     * Get the previous stables the member has belonged to.
     */
    public function previousStables();
}
