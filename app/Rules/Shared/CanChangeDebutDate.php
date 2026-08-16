<?php

declare(strict_types=1);

namespace App\Rules\Shared;

use App\Models\Roster\Stables\Stable;
use App\Models\Titles\Title;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class CanChangeDebutDate implements ValidationRule
{
    public function __construct(private Title|Stable|null $model) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->model) {
            return;
        }

        $currentActivityPeriod = $this->model->currentActivityPeriod;
        if (! $currentActivityPeriod) {
            return;
        }

        $targetDate = Carbon::parse($value);

        if (! $currentActivityPeriod->started_at->isSameDay($targetDate)) {
            $fail("The debut date cannot be changed while {$this->model->name} is currently active.");
        }
    }
}
