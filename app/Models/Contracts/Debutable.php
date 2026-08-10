<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\HasActivityPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @template TStatusChange of Model The status change model class
 * @template TModel of Model The model that can debut
 *
 * @see HasActivityPeriods For the trait implementation
 */
interface Debutable
{
    /**
     * @return HasMany<TStatusChange, TModel>
     */
    public function statusChanges(): HasMany;

    /**
     * @return HasOne<TStatusChange, TModel>
     */
    public function debutStatusChange(): HasOne;

    /**
     * @return HasOne<TStatusChange, TModel>
     */
    public function latestStatusChange(): HasOne;

    public function hasDebuted(): bool;
}
