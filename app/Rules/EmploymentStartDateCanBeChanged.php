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

final class EmploymentStartDateCanBeChanged implements ValidationRule
{
    public function __construct(private Wrestler|Referee|TagTeam|Manager|null $model) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  DateTimeInterface|string|null  $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->model) {
            if ($this->model->isReleased() && ! $this->model->employedOn(Carbon::parse($value))) {
                $fail('employments.validation.employment_released')->translate(['name' => $this->model->getNameLabel()]);
            }

            if ($this->model->isCurrentlyEmployed() && ! $this->model->employedOn(Carbon::parse($value))) {
                $fail('employments.validation.employment_released')->translate(['name' => $this->model->getNameLabel()]);
            }
        }
    }
}
