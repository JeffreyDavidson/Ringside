<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsRetirable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @template TRetirement of Model The retirement model class
 * @template TModel of Model The model that can be retired
 *
 * @see IsRetirable For the trait implementation
 */
interface Retirable
{
    /**
     * @return HasMany<TRetirement, TModel>
     */
    public function retirements(): HasMany;

    /**
     * @return HasOne<TRetirement, TModel>
     */
    public function currentRetirement(): HasOne;

    public function isRetired(): bool;
}
