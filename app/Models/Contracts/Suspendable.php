<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsSuspendable;
use App\Models\Lifecycle\Suspension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TModel of Model
 *
 * @see IsSuspendable
 */
interface Suspendable
{
    /** @return MorphMany<Suspension, TModel> */
    public function suspensions(): MorphMany;

    /** @return MorphOne<Suspension, TModel> */
    public function currentSuspension(): MorphOne;

    public function isSuspended(): bool;
}
