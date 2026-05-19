<?php

declare(strict_types=1);

namespace App\Rules\Shared;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Validates that an employment date can be changed for any employable model.
 */
class CanChangeEmploymentDate implements ValidationRule
{
    public function __construct(private ?Model $model) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->model || ! method_exists($this->model, 'isEmployed')) {
            return;
        }

        $targetDate = Carbon::parse($value);

        if ($this->model->isEmployed()) {
            if (method_exists($this->model, 'employedOn') && ! $this->model->employedOn($targetDate)) {
                $modelName = $this->getModelName();
                $fail("The employment date cannot be changed while {$modelName} is currently employed.");
            }
        }
    }

    private function getModelName(): string
    {
        return $this->model->getAttribute('name') ?? class_basename($this->model);
    }
}
