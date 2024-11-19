<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface CanBeAStableMember
{
    public function stables(): BelongsToMany;

    public function currentStable(): BelongsToOne;

    public function previousStables(): BelongsToMany;
}
