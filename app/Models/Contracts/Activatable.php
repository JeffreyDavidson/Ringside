<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface Activatable
{
    public function activations(): HasMany;

    public function currentActivation(): HasOne;

    public function previousActivations(): HasMany;

    public function previousActivation(): HasOne;

    public function isActive(): bool;

    public function hasActivations(): bool;
}
