<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsRetirable;
use App\Models\Lifecycle\Retirement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TModel of Model
 *
 * @see IsRetirable
 */
interface Retirable
{
    /** @return MorphMany<Retirement, TModel> */
    public function retirements(): MorphMany;

    /** @return MorphOne<Retirement, TModel> */
    public function currentRetirement(): MorphOne;

    public function isRetired(): bool;
}
