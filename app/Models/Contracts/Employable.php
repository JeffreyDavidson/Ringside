<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

interface Employable
{
    public function employments(): HasMany;

    public function currentEmployment(): HasOne;

    public function futureEmployment(): HasOne;

    public function previousEmployments(): HasMany;

    public function previousEmployment(): HasOne;

    public function hasEmployments(): bool;

    public function isCurrentlyEmployed(): bool;

    public function hasFutureEmployment(): bool;

    public function isNotInEmployment(): bool;

    public function isUnemployed(): bool;

    public function isReleased(): bool;

    public function employedOn(Carbon $employmentDate): bool;

    public function employedBefore(Carbon $employmentDate): bool;
}
