<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface Retirable
{
    public function retirements(): HasMany;

    public function currentRetirement(): HasOne;

    public function previousRetirements(): HasMany;

    public function previousRetirement(): HasOne;

    public function isRetired(): bool;

    public function hasRetirements(): bool;
}
