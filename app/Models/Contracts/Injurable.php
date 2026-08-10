<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsInjurable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @template TInjury of Model The injury model class
 * @template TModel of Model The model that can be injured
 *
 * @see IsInjurable For the trait implementation
 */
interface Injurable
{
    /**
     * @return HasMany<TInjury, TModel>
     */
    public function injuries(): HasMany;

    /**
     * @return HasOne<TInjury, TModel>
     */
    public function currentInjury(): HasOne;

    public function isInjured(): bool;
}
