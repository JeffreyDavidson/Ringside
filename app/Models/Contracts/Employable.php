<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Employment;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

interface Employable extends Identifiable
{
    /**
     * Get all the employments of the model.
     *
     * @return MorphMany<Employment>
     */
    public function employments(): MorphMany;

    /**
     * Get the first employment of the model.
     *
     * @return MorphOne<Employment>
     */
    public function firstEmployment(): MorphOne;

    /**
     * Get the current employment of the model.
     *
     * @return MorphOne<Employment>
     */
    public function currentEmployment(): MorphOne;

    /**
     * Get the future employment of the model.
     *
     * @return MorphOne<Employment>
     */
    public function futureEmployment(): MorphOne;

    /**
     * Get the previous employments of the model.
     *
     * @return MorphMany<Employment>
     */
    public function previousEmployments(): MorphMany;

    /**
     * Get the previous employment of the model.
     *
     * @return MorphOne<Employment>
     */
    public function previousEmployment(): MorphOne;

    public function isCurrentlyEmployed(): bool;

    public function startDateWas(Carbon $employmentDate): bool;

    public function hasEmployments(): bool;

    public function isNotInEmployment(): bool;

    public function isUnemployed(): bool;

    public function hasFutureEmployment(): bool;

    public function isReleased(): bool;

    /**
     * @return Attribute<string, never>
     */
    public function startedAt(): Attribute;

    public function employedOn(Carbon $employmentDate): bool;

    public function scheduledToBeEmployedOn(Carbon $employmentDate): bool;

    public function employedBefore(Carbon $employmentDate): bool;

    public function employedAfter(Carbon $employmentDate): bool;

    public function futureEmploymentIsBefore(Carbon $employmentDate): bool;
}
