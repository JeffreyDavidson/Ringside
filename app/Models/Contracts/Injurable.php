<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface Injurable
{
    public function injuries(): HasMany;

    public function currentInjury(): HasOne;

    public function previousInjuries(): HasMany;

    public function previousInjury(): HasOne;

    public function isInjured(): bool;

    public function hasInjuries(): bool;
}
