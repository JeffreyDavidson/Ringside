<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Fidum\EloquentMorphToOne\MorphToOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface CanBeAStableMember
{
    public function stables(): MorphToMany;

    public function currentStable(): MorphToOne;

    public function previousStables(): MorphToMany;
}
