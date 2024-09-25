<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Employable
{
    /**
     * Get all the employments of the model.
     */
    public function employments(): HasMany;
}
