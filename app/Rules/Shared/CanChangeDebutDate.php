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
            $fail(__('core.validation.debut_date_invalid'));

            return;
        }

        $targetDate = Carbon::parse($value);

        $debutDate = ($this->model->firstActivityPeriod ?? $currentActivityPeriod)?->started_at;

        if ($debutDate === null || $debutDate->isSameDay($targetDate)) {
            return;
        }

        if ($hasSeveralStablePeriods) {
            $fail(__('core.validation.debut_date_multiple_periods', ['name' => $this->model->name]));

            return;
        }

        $fail(__('core.validation.debut_date_active', ['name' => $this->model->name]));
    }
}
