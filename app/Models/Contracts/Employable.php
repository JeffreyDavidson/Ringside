<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsEmployable;
use App\Models\Lifecycle\Employment;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TModel of Model The model that can be employed
 *
 * @see IsEmployable For the trait implementation
 */
interface Employable
{
    /**
     * @return MorphMany<Employment, TModel>
     */
    public function employments(): MorphMany;

    /**
     * @return MorphOne<Employment, TModel>
     */
    public function currentEmployment(): MorphOne;

    /**
     * @return MorphOne<Employment, TModel>
     */
    public function futureEmployment(): MorphOne;

    public function employedOn(DateTimeInterface $date): bool;
}
