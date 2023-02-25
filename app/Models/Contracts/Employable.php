<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

interface Employable
{
    public function employments(): MorphMany;

    public function firstEmployment(): MorphOne;

    public function currentEmployment(): MorphOne;

    public function futureEmployment(): MorphOne;

    public function previousEmployments(): MorphMany;

    public function previousEmployment(): MorphOne;

    public function isCurrentlyEmployed(): bool;

    public function startDateWas(Carbon $employmentDate): bool;

    public function hasEmployments(): bool;

    public function isNotInEmployment(): bool;

    public function isUnemployed(): bool;

    public function hasFutureEmployment(): bool;

    public function isReleased(): bool;

    public function startedAt(): Attribute;

    public function employedOn(Carbon $employmentDate): bool;

    public function scheduledToBeEmployedOn(Carbon $employmentDate): bool;

    public function employedBefore(Carbon $employmentDate): bool;

    public function employedAfter(Carbon $employmentDate): bool;

    public function futureEmploymentIsBefore(Carbon $employmentDate): bool;

    public function canHaveEmploymentStartDateChanged(Carbon $employmentDate): bool;
}
