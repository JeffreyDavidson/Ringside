<?php

declare(strict_types=1);

namespace App\Rules\Shared;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Validates that a debut date can be changed for any model with debut logic.
 *
 * Works with any model that has debut functionality (Titles, Stables, etc.)
 * A debut date is when the entity is first introduced/launched in storylines.
 */
class CanChangeDebutDate implements ValidationRule
{
    public function __construct(private ?Model $model) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->model) {
            return;
        }

        if (! method_exists($this->model, 'isCurrentlyActive') ||
            ! method_exists($this->model, 'wasActiveOn')) {
            return;
        }

        $targetDate = Carbon::parse($value);

        if ($this->model->isCurrentlyActive() && ! $this->model->wasActiveOn($targetDate)) {
            $modelName = $this->getModelName();
            $fail("The debut date cannot be changed while {$modelName} is currently active.");
        }
    }

    private function getModelName(): string
    {
        if (method_exists($this->model, 'getDisplayName')) {
            return $this->model->getDisplayName();
        }

        $name = $this->model->getAttribute('name');
        if ($name !== null) {
            return $name;
        }

        return class_basename($this->model);
    }
}
