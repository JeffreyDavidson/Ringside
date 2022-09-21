<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Support\Carbon;

interface Employable
{
    public function employments(): \Illuminate\Database\Eloquent\Relations\MorphMany;

    public function firstEmployment(): \Illuminate\Database\Eloquent\Relations\MorphOne;

    public function currentEmployment(): \Illuminate\Database\Eloquent\Relations\MorphOne;

    public function futureEmployment(): \Illuminate\Database\Eloquent\Relations\MorphOne;

    public function previousEmployments(): \Illuminate\Database\Eloquent\Relations\MorphMany;

    public function previousEmployment(): \Illuminate\Database\Eloquent\Relations\MorphOne;

    public function isCurrentlyEmployed(): bool;

    public function startDateWas(Carbon $employmentDate): bool;

    public function hasEmployments(): bool;

    public function isNotInEmployment(): bool;

    public function isUnemployed(): bool;

    public function hasFutureEmployment(): bool;

    public function isReleased(): bool;

    public function startedAt(): \Illuminate\Database\Eloquent\Casts\Attribute;

    public function employedOn(Carbon $employmentDate): bool;

    public function scheduledToBeEmployedOn(Carbon $employmentDate): bool;

    public function employedBefore(Carbon $employmentDate): bool;

    public function employedAfter(Carbon $employmentDate): bool;

    public function futureEmploymentIsBefore(Carbon $employmentDate): bool;

    public function canHaveEmploymentStartDateChanged(Carbon $employmentDate): bool;
}
