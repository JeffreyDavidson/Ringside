<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Fidum\EloquentMorphToOne\MorphToOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface CanBeAStableMember
{
    /**
     * Get the stables the stable member has been a member of.
     *
     * @return MorphToMany<\App\Models\Stable>
     */
    public function stables(): MorphToMany;

    /**
     * Get the current stable the member belongs to.
     */
    public function currentStable(): MorphToOne;

    /**
     * Get the previous stables the member has belonged to.
     *
     * @return MorphToMany<\App\Models\Stable>
     */
    public function previousStables(): MorphToMany;
}
