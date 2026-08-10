<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsSuspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @template TSuspension of Model The suspension model class
 * @template TModel of Model The model that can be suspended
 *
 * @see IsSuspendable For the trait implementation
 */
interface Suspendable
{
    /**
     * @return HasMany<TSuspension, TModel>
     */
    public function suspensions(): HasMany;

    /**
     * @return HasOne<TSuspension, TModel>
     */
    public function currentSuspension(): HasOne;

    public function isSuspended(): bool;
}
