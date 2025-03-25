<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Retirable
{
    public function retirements(): HasMany;
}
