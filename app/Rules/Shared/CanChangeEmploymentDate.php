<?php

declare(strict_types=1);

namespace App\Rules\Shared;

use App\Models\Contracts\Employable;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Validates that an employment date can be changed for any employable model.
 */
class CanChangeEmploymentDate implements ValidationRule
{
    /**
     * @param  (Model&Employable<*>)|null  $model
     */
    public function __construct(private readonly (Model&Employable)|null $model) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->model === null) {
            return;
        }

        if (! $value instanceof DateTimeInterface && ! is_float($value) && ! is_int($value) && ! is_string($value)) {
            $fail('The employment date must be a valid date.');

            return;
        }

        $targetDate = Carbon::parse($value);

        if (! $this->model->currentEmployment()->exists()) {
            return;
        }

        if (! $this->model->employedOn($targetDate)) {
            $modelName = $this->getModelName($this->model);
            $fail("The employment date cannot be changed while {$modelName} is currently employed.");
        }
    }

    private function getModelName(Model $model): string
    {
        $name = $model->getAttribute('name');

        return is_string($name) ? $name : class_basename($model);
    }
}
