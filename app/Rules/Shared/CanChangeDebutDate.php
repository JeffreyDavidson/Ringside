<?php

declare(strict_types=1);

namespace App\Rules\Shared;

use App\Models\Roster\Stables\Stable;
use App\Models\Titles\Title;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class CanChangeDebutDate implements ValidationRule
{
    public function __construct(private readonly Title|Stable|null $model) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->model) {
            return;
        }

        $currentActivityPeriod = $this->model->currentActivityPeriod;
        $hasSeveralStablePeriods = $this->model instanceof Stable && $this->model->activityPeriods()->count() > 1;

        if (! $currentActivityPeriod && ! $hasSeveralStablePeriods) {
            return;
        }

        if (! $value instanceof DateTimeInterface && ! is_float($value) && ! is_int($value) && ! is_string($value)) {
            $fail('The debut date must be a valid date.');

            return;
        }

        $targetDate = Carbon::parse($value);

        $debutDate = ($this->model->firstActivityPeriod ?? $currentActivityPeriod)?->started_at;

        if ($debutDate === null || $debutDate->isSameDay($targetDate)) {
            return;
        }

        if ($hasSeveralStablePeriods) {
            $fail("The debut date cannot be changed because {$this->model->name} has been active in more than one period.");

            return;
        }

        $fail("The debut date cannot be changed while {$this->model->name} is currently active.");
    }
}
