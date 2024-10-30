<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface Suspendable
{
    public function suspensions(): HasMany;

    public function currentSuspension(): HasOne;

    public function previousSuspensions(): HasMany;

    public function previousSuspension(): HasOne;

    public function isSuspended(): bool;

    public function hasSuspensions(): bool;
}
