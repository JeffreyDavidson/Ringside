<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Activatable
{
    /**
     * @return HasMany<Model, $this>
     */
    public function activations(): HasMany;
}
