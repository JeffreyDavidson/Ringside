<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Activatable
{
    public function activations(): HasMany;
}
