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
        $model = $this->model;

        if (! $model || ! method_exists($model, 'isEmployed')) {
            return;
        }

        $targetDate = Carbon::parse($value);

        if ($model->isEmployed()) {
            if (method_exists($model, 'employedOn') && ! $model->employedOn($targetDate)) {
                $modelName = $this->getModelName($model);
                $fail("The employment date cannot be changed while {$modelName} is currently employed.");
            }
        }
    }

    private function getModelName(Model $model): string
    {
        return $model->getAttribute('name') ?? class_basename($model);
    }
}
