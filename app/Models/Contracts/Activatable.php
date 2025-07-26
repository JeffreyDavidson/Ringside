<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Stables\StableActivation;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
interface Activatable
{
    /**
     * @return HasMany<StableActivation, TDeclaringModel>
     */
    public function activations(): HasMany;
}
