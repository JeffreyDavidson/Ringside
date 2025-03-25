<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Manageable
{
    public function managers(): BelongsToMany;

    public function currentManagers(): BelongsToMany;

    public function previousManagers(): BelongsToMany;
}
