<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Fidum\EloquentMorphToOne\MorphToOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface CanBeAStableMember
{
    /**
     * Get the stables the stable member has been a member of.
     */
    public function stables(): MorphToMany;

    /**
     * Get the current stable the member belongs to.
     */
    public function currentStable(): MorphToOne;

    /**
     * Get the previous stables the member has belonged to.
     */
    public function previousStables(): MorphToMany;
}
