<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Activatable
{
    /**
     * Get all the activations of the model.
     */
    public function activations(): HasMany;
}
