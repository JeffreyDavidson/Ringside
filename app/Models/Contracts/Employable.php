<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsEmployable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @template TEmployment of Model The employment model class
 * @template TModel of Model The model that can be employed
 *
 * @see IsEmployable For the trait implementation
 */
interface Employable
{
    /**
     * @return HasMany<TEmployment, TModel>
     */
    public function employments(): HasMany;

    /**
     * @return HasOne<TEmployment, TModel>
     */
    public function currentEmployment(): HasOne;

    public function isEmployed(): bool;

    public function isReleased(): bool;
}
