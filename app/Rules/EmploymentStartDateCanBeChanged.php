<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class EmploymentStartDateCanBeChanged implements ValidationRule
{
    public function __construct(protected Wrestler|Referee|TagTeam|Manager $model) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  DateTimeInterface|string|null  $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->model->isReleased() && ! $this->model->employedOn(Carbon::parse($value))) {
            $fail("{$this->model->getNameLabel()} was released and the start date cannot be changed.");
        }

        if ($this->model->isCurrentlyEmployed() && ! $this->model->employedOn(Carbon::parse($value))) {
            $fail("{$this->model->getNameLabel()} is currently employed and the start date cannot be changed.");
        }
    }
}
