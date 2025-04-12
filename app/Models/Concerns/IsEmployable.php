<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @phpstan-require-implements \App\Models\Contracts\Employable
 */
trait IsEmployable
{
    public function currentEmployment(): HasOne
    {
        return $this->employments()
            ->whereNull('ended_at')
            ->one();
    }

    public function futureEmployment(): HasOne
    {
        return $this->employments()
            ->whereNull('ended_at')
            ->where('started_at', '>', now())
            ->one();
    }

    public function previousEmployments(): HasMany
    {
        return $this->employments()
            ->whereNotNull('ended_at');
    }

    public function previousEmployment(): HasOne
    {
        return $this->previousEmployments()
            ->one()
            ->ofMany('ended_at', 'max');
    }

    public function firstEmployment(): HasOne
    {
        return $this->employments()
            ->one()
            ->ofMany('started_at', 'min');
    }

    public function hasEmployments(): bool
    {
        return $this->employments()->count() > 0;
    }

    public function isCurrentlyEmployed(): bool
    {
        return $this->currentEmployment()->exists();
    }

    public function hasFutureEmployment(): bool
    {
        return $this->futureEmployment()->exists();
    }

    public function isNotInEmployment(): bool
    {
        return $this->isUnemployed() || $this->isReleased() || $this->isRetired();
    }

    public function isUnemployed(): bool
    {
        return $this->status === EmploymentStatus::Unemployed;
    }

    public function isReleased(): bool
    {
        return $this->status === EmploymentStatus::Released;
    }

    public function employedOn(Carbon $employmentDate): bool
    {
        return $this->currentEmployment ? $this->currentEmployment->started_at->eq($employmentDate) : false;
    }

    public function employedBefore(Carbon $employmentDate): bool
    {
        return $this->currentEmployment ? $this->currentEmployment->started_at->lte($employmentDate) : false;
    }

    public function getFormattedFirstEmployment(): string
    {
        return $this->hasEmployments() ? ($this->firstEmployment?->started_at->format('Y-m-d') ?? 'TBD') : 'TBD';
    }
}
